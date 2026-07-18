<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\User\CompanyUser;
use App\Entity\User\Enums\UserTypes;
use App\Entity\User\PasswordReset;
use App\Entity\User\User;
use App\Form\User\ChangePasswordFormType;
use App\Form\User\MfaEnableFormType;
use App\Form\User\PasswordResetRequestFormType;
use App\Form\User\SetPasswordFormType;
use App\Form\User\SudoConfirmFormType;
use App\Message\User\PasswordResetRequestEmail;
use App\Repository\User\ExternalAppAuthenticationRepository;
use App\Repository\User\PasswordResetRepository;
use App\Security\User\BackupCodeManager;
use App\Security\User\HandlerRegistry;
use App\Security\User\MfaPolicy;
use App\Security\User\SudoMode;
use App\Service\Application\AltchaSolutionGuard;
use App\Service\User\SessionManager;
use App\Util\Application\SplitToken;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use OTPHP\TOTP;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

use function assert;
use function bin2hex;
use function is_int;
use function is_string;
use function random_bytes;
use function sprintf;
use function str_starts_with;
use function strval;
use function trim;

abstract class AbstractSecurityController extends AbstractController
{
    public function __construct(
        protected readonly TranslatorInterface $translator,
        protected readonly SessionManager $sessionManager,
        protected readonly HandlerRegistry $registry,
        #[Autowire(service: 'security.firewall.map')]
        protected readonly FirewallMap $firewallMap,
        protected readonly string $routePrefix,
        protected readonly UserTypes $userType,
    ) {
    }

    #[Route(
        path: '/login',
        name: 'login',
    )]
    public function loginAction(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render(
            'user/login.html.twig',
            [
                'type' => $this->userType->value,
                'last_username' => $authenticationUtils->getLastUsername(),
                'error' => $authenticationUtils->getLastAuthenticationError(),
            ],
        );
    }

    #[Route(
        path: '/logout',
        name: 'logout',
    )]
    public function logoutAction(): void
    {
        throw new LogicException(
            'This method can be blank - it will be intercepted by the logout key on your firewall.',
        );
    }

    #[Route(
        path: '/forgot-password',
        name: 'forgot_password',
    )]
    public function passwordResetRequest(
        Request $request,
        #[Autowire(service: 'limiter.password_reset_ip')]
        RateLimiterFactory $passwordResetIpLimiter,
        #[Autowire(service: 'limiter.password_reset_credentials')]
        RateLimiterFactory $passwordResetCredentialsLimiter,
        MessageBusInterface $bus,
        AltchaSolutionGuard $altchaSolutionGuard,
    ): Response {
        $form = $this->createForm(
            PasswordResetRequestFormType::class,
            null,
            $this->passwordResetRequestFormOptions(),
        )->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->render(
                'user/password-reset-request.html.twig',
                [
                    'form' => $form,
                    'type' => $this->userType,
                ],
            );
        }

        // Single-use: the local Altcha validator accepts a solved proof-of-work repeatedly within its signature
        // window, so reject a replay even though the captcha just validated.
        if (!$altchaSolutionGuard->consume(strval($form->get('security')->getData()))) {
            $this->addFlash(
                AlertTypes::Danger->value,
                $this->translator->trans('Please complete the verification again and resubmit.'),
            );

            return $this->redirectToRoute($this->routePrefix . 'forgot_password');
        }

        // Consume both limiters at the same time. To ensure that if an IP gets limited, the credentials also gets
        // limited. Otherwise, changing IPs is always cheaper.
        $ipLimit = $passwordResetIpLimiter->create($request->getClientIp())->consume();
        $credLimit = $passwordResetCredentialsLimiter->create($this->buildPasswordResetCredentialKey($form))->consume();
        if (
            false === $ipLimit->isAccepted()
            || false === $credLimit->isAccepted()
        ) {
            $this->addFlash(
                AlertTypes::Danger->value,
                $this->translator->trans(
                    'You have sent too many requests in a short period of time. Please wait a few minutes before trying again.', // phpcs:ignore Generic.Files.LineLength.TooLong -- user-visible strings should not be split
                ),
            );

            return $this->redirectToRoute($this->routePrefix . 'forgot_password');
        }

        $bus->dispatch($this->buildPasswordResetMessage($form));

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans(
                'If there is an account associated with your email address, an email has just been sent containing a link that will allow you to reset your password. If you do not receive an email, please check your spam folder or try again later.', // phpcs:ignore Generic.Files.LineLength.TooLong -- user-visible strings should not be split
            ),
        );

        return $this->redirectToRoute($this->routePrefix . 'forgot_password');
    }

    /**
     * When users request a link to reset their password, we do not want the token in that URL to end up in the
     * browser's history or accessible to any third-party resources loaded by the page.
     *
     * As such, we validate the token, generate a single-use temp hash, persists it, and 302-redirects to the temp-hash
     * URL so the original token never appears on the page that may load third-party resources.
     *
     * It does not suffice to simply store the original token in a session, because the session cookie is
     * `SameSite=Strict` and thus not sent on the initial navigation from the email (which will be from a different
     * origin, e.g. gmail.com).
     */
    #[Route(
        path: '/reset-password/{token}',
        name: 'password_reset_claim',
        requirements: ['token' => '[0-9a-f]{32}\.[0-9a-f]{64}'],
        methods: ['GET'],
    )]
    public function passwordResetClaim(
        string $token,
        PasswordResetRepository $passwordResetRepository,
        EntityManagerInterface $em,
    ): Response {
        $split = SplitToken::split($token);
        assert(null !== $split);
        $passwordReset = $passwordResetRepository->findBySelector($split['selector']);

        // Any validation failure must result in a 404 (never leak which check failed - though timing can be an issue).
        if (
            null === $passwordReset
            || $this->userType !== $passwordReset->getUserType()
            || $passwordReset->isExpired()
            || !SplitToken::matches(
                $passwordReset->getHashedToken(),
                $split['verifier'],
                PasswordReset::HASH_ALGO,
            )
        ) {
            throw new NotFoundHttpException();
        }

        $tempHash = bin2hex(random_bytes(32));
        $passwordReset->setTempHash($tempHash);
        $passwordReset->setTempHashExpiresAt(new DateTimeImmutable('now')->add(new DateInterval('PT3M')));
        $em->flush();

        return $this->withNoLeakHeaders($this->redirectToRoute(
            $this->routePrefix . 'password_reset',
            ['th' => $tempHash],
        ));
    }

    /**
     * This is stage 2 (GET with ?th=...) for the password (re)set. We consume the temp hash, bind the `PasswordReset`
     * id to the session, and render the form.
     *
     * Finally, on POST we read the bound id from the session and apply the new password. Because the form action is the
     * bare `/reset-password` URL (no `?th=`), the POST is a same-origin navigation initiated from the rendered stage-2
     * page. That is the request on which the SameSite=Strict session cookie can finally be sent, even if the original
     * click came from another origin.
     */
    #[Route(
        path: '/reset-password',
        name: 'password_reset',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function passwordResetForm(
        Request $request,
        PasswordResetRepository $passwordResetRepository,
        EntityManagerInterface $em,
    ): Response {
        $session = $request->getSession();
        $sessionKey = $this->passwordResetSessionKey();

        if ($request->isMethod('GET')) {
            $tempHash = $request->query->get('th');

            if (null !== $tempHash) {
                $passwordReset = $passwordResetRepository->findByTempHash($tempHash);

                if (
                    null === $passwordReset
                    || $this->userType !== $passwordReset->getUserType()
                    || $passwordReset->isTempHashExpired()
                ) {
                    return $this->staleLinkRedirect();
                }

                // Single-use: clear the temp hash before binding to the session.
                $passwordReset->setTempHash(null);
                $passwordReset->setTempHashExpiresAt(null);
                $em->flush();

                $session->set(
                    $sessionKey,
                    $passwordReset->getId(),
                );

                return $this->withNoLeakHeaders($this->renderPasswordResetForm());
            }

            // No `?th=`: render from session (e.g. user navigated back), else send them to request a new link.
            if (!is_int($session->get($sessionKey))) {
                return $this->staleLinkRedirect();
            }

            return $this->withNoLeakHeaders($this->renderPasswordResetForm());
        }

        $passwordResetId = $session->get($sessionKey);
        if (!is_int($passwordResetId)) {
            return $this->staleLinkRedirect();
        }

        $passwordReset = $passwordResetRepository->find($passwordResetId);
        // Defence in depth: even with a valid session id, re-validate the underlying state.
        if (
            null === $passwordReset
            || $this->userType !== $passwordReset->getUserType()
            || $passwordReset->isExpired()
        ) {
            $session->remove($sessionKey);

            return $this->staleLinkRedirect();
        }

        $target = $this->resolvePasswordResetTarget($passwordReset);
        if (null === $target) {
            $session->remove($sessionKey);

            return $this->staleLinkRedirect();
        }

        $form = $this->createForm(
            SetPasswordFormType::class,
            $target,
        )->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->withNoLeakHeaders($this->renderPasswordResetForm($form));
        }

        // Password was hashed and assigned by the form via `hash_property_path`.
        $target->setPasswordChangedOn(new DateTime());
        $em->persist($target);
        $this->deletePasswordResetsForTarget($passwordReset);
        $em->flush();

        $session->remove($sessionKey);

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('Your password has been reset. You can now sign in with your new password.'),
        );

        return $this->redirectToRoute($this->routePrefix . 'login');
    }

    #[IsGranted('SUDO')]
    #[Route(
        path: '/security',
        name: 'security_index',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function security(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
        MfaPolicy $mfaPolicy,
        ExternalAppAuthenticationRepository $externalAppAuthenticationRepository,
        #[CurrentUser]
        User|CompanyUser $user,
    ): Response {
        $firewall = $this->firewall($request);

        // External applications only authenticate members, so company users never have any.
        $externalApps = $user instanceof User
            ? $externalAppAuthenticationRepository->getFirstAndLastAuthenticationPerExternalApp($user->getMember())
            : [];

        $form = $this->createForm(
            ChangePasswordFormType::class,
            $user,
        )
            ->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            $currentPassword = $form->get('currentPassword')->getData();
            if (
                $hasher->isPasswordValid(
                    $user,
                    $currentPassword,
                )
            ) {
                $newPassword = $form->get('plainPassword')->getData();
                $user->setPassword($hasher->hashPassword(
                    $user,
                    $newPassword,
                ));
                $user->setPasswordChangedOn(new DateTime());
                $em->flush();

                $this->addFlash(
                    AlertTypes::Success->value,
                    $this->translator->trans('Password updated successfully.'),
                );

                return $this->redirectToRoute($this->routePrefix . 'security_index');
            }

            $form->get('currentPassword')->addError(new FormError(
                $this->translator->trans('Incorrect current password.'),
            ));
        }

        return $this->render(
            'user/security/index.html.twig',
            [
                'form' => $form,
                'sessions' => $this->sessionManager->getActiveSessions(
                    $user,
                    $firewall,
                ),
                'currentSeries' => $this->sessionManager->currentSeries(
                    $request,
                    $firewall,
                ),
                'mfaEnabled' => $user->isTotpAuthenticationEnabled(),
                'mfaRequired' => $user instanceof User && $mfaPolicy->isRequiredFor($user),
                'externalApps' => $externalApps,
                'routePrefix' => $this->routePrefix,
            ],
        );
    }

    #[IsGranted('SUDO')]
    #[IsCsrfTokenValid(
        id: new Expression('"session_guard_terminate-" ~ args["series"]'),
        tokenKey: '_csrf_token',
    )]
    #[Route(
        path: '/security/sessions/{series}/terminate',
        name: 'session_terminate',
        methods: ['POST'],
    )]
    public function sessionTerminate(
        string $series,
        Request $request,
        #[CurrentUser]
        User|CompanyUser $user,
    ): Response {
        $firewall = $this->firewall($request);

        $success = $this->sessionManager->terminateSession(
            $user,
            $series,
            $request,
            $firewall,
        );

        if (!$success) {
            $this->addFlash(
                'warning',
                'Session not found or already expired.',
            );

            return $this->redirectToRoute($this->routePrefix . 'security_index');
        }

        $handler = $this->registry->get($firewall);
        if (null === $handler) {
            return $this->redirectToRoute($this->routePrefix . 'security_index');
        }

        $currentSeries = $handler->getSeriesFromCookie($request);

        if ($currentSeries === $series) {
            $handler->clearRememberMeCookie();
            $request->getSession()->invalidate();

            return $this->redirectToRoute($this->routePrefix . 'login');
        }

        $this->addFlash(
            'success',
            'Session terminated successfully.',
        );

        return $this->redirectToRoute($this->routePrefix . 'security_index');
    }

    #[IsGranted('SUDO')]
    #[IsCsrfTokenValid(
        id: 'session_guard_terminate_others',
        tokenKey: '_csrf_token',
    )]
    #[Route(
        path: '/security/sessions/terminate-others',
        name: 'session_terminate_others',
        methods: ['POST'],
    )]
    public function sessionTerminateOthers(
        Request $request,
        #[CurrentUser]
        User|CompanyUser $user,
    ): Response {
        $count = $this->sessionManager->terminateAllExceptCurrent(
            $user,
            $request,
            $this->firewall($request),
        );

        $this->addFlash(
            'success',
            sprintf(
                '%d other session%s terminated.',
                $count,
                1 !== $count ? 's' : '',
            ),
        );

        return $this->redirectToRoute($this->routePrefix . 'security_index');
    }

    #[IsGranted('SUDO')]
    #[IsCsrfTokenValid(
        id: 'session_guard_terminate_all',
        tokenKey: '_csrf_token',
    )]
    #[Route(
        path: '/security/sessions/terminate-all',
        name: 'session_terminate_all',
        methods: ['POST'],
    )]
    public function sessionTerminateAll(
        Request $request,
        #[CurrentUser]
        User|CompanyUser $user,
    ): Response {
        $firewall = $this->firewall($request);

        $this->sessionManager->terminateAll(
            $user,
            $firewall,
        );
        $this->registry->get($firewall)?->clearRememberMeCookie();
        $request->getSession()->invalidate();

        return $this->redirectToRoute($this->routePrefix . 'login');
    }

    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    #[Route(
        path: '/sudo',
        name: 'sudo_confirm',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function confirmSudo(
        Request $request,
        UserPasswordHasherInterface $hasher,
        SudoMode $sudoMode,
        TotpAuthenticatorInterface $totpAuthenticator,
        BackupCodeManager $backupCodeManager,
        #[Autowire(service: 'limiter.sudo_confirm_ip')]
        RateLimiterFactory $sudoConfirmIpLimiter,
        #[Autowire(service: 'limiter.sudo_confirm_credentials')]
        RateLimiterFactory $sudoConfirmCredentialsLimiter,
        #[CurrentUser]
        User|CompanyUser $user,
    ): Response {
        $mfaRequired = $user->isTotpAuthenticationEnabled();
        $sudoConfirmRouteName = $this->routePrefix . 'sudo_confirm';
        $next = $this->safeNextUrl($request->query->getString('next'));

        if ($sudoMode->isActive()) {
            return new RedirectResponse($next);
        }

        $form = $this->createForm(
            SudoConfirmFormType::class,
            null,
            [
                'mfa_required' => $mfaRequired,
            ],
        )->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->renderSudoConfirm(
                $form,
                $sudoConfirmRouteName,
                $next,
            );
        }

        // Consume both limiters at the same time. To ensure that if an IP gets limited, the credentials also gets
        // limited. Otherwise, changing IPs is always cheaper.
        $ipLimit = $sudoConfirmIpLimiter->create($request->getClientIp())->consume();
        $credLimit = $sudoConfirmCredentialsLimiter->create(bin2hex($user->getUserIdentifier()))->consume();
        if (
            false === $ipLimit->isAccepted()
            || false === $credLimit->isAccepted()
        ) {
            $form->addError(new FormError(
                $this->translator->trans(
                    'You have sent too many requests in a short period of time. Please wait a few minutes before trying again.', // phpcs:ignore Generic.Files.LineLength.TooLong -- user-visible strings should not be split
                ),
            ));

            return $this->renderSudoConfirm(
                $form,
                $sudoConfirmRouteName,
                $next,
            );
        }

        $password = strval($form->get('password')->getData());
        $mfaCode = trim(strval($form->has('mfaCode') ? $form->get('mfaCode')->getData() : ''));

        // Always run every check, regardless of which one fails. A single combined "Incorrect credentials." error
        // hides which factor was wrong from the response body; running the MFA path even on a wrong-password attempt
        // hides which factor was wrong from response timing too.
        $passwordOk = $hasher->isPasswordValid(
            $user,
            $password,
        );

        $totpOk = $mfaRequired && '' !== $mfaCode && $totpAuthenticator->checkCode(
            $user,
            $mfaCode,
        );
        $backupOk = $mfaRequired && !$totpOk && '' !== $mfaCode && $backupCodeManager->isBackupCode(
            $user,
            $mfaCode,
        );
        $mfaOk = !$mfaRequired || $totpOk || $backupOk;

        if (
            !$passwordOk
            || !$mfaOk
        ) {
            // Attach the same generic error to every credential field so the response body never reveals which factor
            // was wrong. Pairs with the always-run-every-check block above (which closes the timing side-channel).
            $errorMessage = $this->translator->trans('Incorrect credentials.');
            $form->get('password')->addError(new FormError($errorMessage));
            if ($form->has('mfaCode')) {
                $form->get('mfaCode')->addError(new FormError($errorMessage));
            }

            return $this->renderSudoConfirm(
                $form,
                $sudoConfirmRouteName,
                $next,
            );
        }

        // Burn the backup code only after BOTH factors confirmed. An attacker with a stolen backup code but the wrong
        // password must not be able to invalidate the code by submitting it.
        if ($backupOk) {
            $backupCodeManager->invalidateBackupCode(
                $user,
                $mfaCode,
            );
        }

        $sudoMode->grant();

        return new RedirectResponse($next);
    }

    #[IsGranted('SUDO')]
    #[Route(
        path: '/security/mfa/enable',
        name: 'mfa_enable',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function mfaEnable(
        Request $request,
        EntityManagerInterface $em,
        BackupCodeManager $backupCodeManager,
        #[CurrentUser]
        User|CompanyUser $user,
    ): Response {
        if ($user->isTotpAuthenticationEnabled()) {
            return $this->redirectToRoute($this->routePrefix . 'security_index');
        }

        $session = $request->getSession();

        // Regenerate the secret on every GET so an abandoned setup cannot be resumed with the previously-shown QR.
        if ($request->isMethod('GET')) {
            $secret = TOTP::generate()->getSecret();
            $session->set(
                '_mfa_setup_secret',
                $secret,
            );
        } else {
            $secret = $session->get('_mfa_setup_secret');
            if (
                !is_string($secret)
                || '' === $secret
            ) {
                return $this->redirectToRoute($this->routePrefix . 'mfa_enable');
            }
        }

        $totp = TOTP::createFromSecret($secret)
            ->withLabel($user->getUserIdentifier())
            ->withIssuer('GEWIS');

        $form = $this->createForm(MfaEnableFormType::class)->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            $code = trim(strval($form->get('code')->getData()));

            if (
                '' !== $code
                && $totp->verify($code)
            ) {
                $user->setTotpSecret($secret);
                $user->setForceReloginAt(new DateTime()); // Ensures that all sessions use MFA.
                $em->flush();

                $plaintext = $backupCodeManager->generateAndStore($user);

                $session->remove('_mfa_setup_secret');
                $session->set(
                    '_mfa_pending_backup_codes',
                    $plaintext,
                );

                $this->addFlash(
                    AlertTypes::Success->value,
                    $this->translator->trans('MFA enabled. Save your backup codes now - they will not be shown again.'),
                );

                return $this->redirectToRoute($this->routePrefix . 'mfa_backup_codes');
            }

            $form->get('code')->addError(new FormError(
                $this->translator->trans(
                    'code_invalid',
                    [],
                    'SchebTwoFactorBundle',
                ),
            ));
        }

        return $this->render(
            'user/security/mfa/enable.html.twig',
            [
                'secret' => $secret,
                'provisioningUri' => $totp->getProvisioningUri(),
                'routePrefix' => $this->routePrefix,
                'form' => $form,
            ],
        );
    }

    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    #[Route(
        path: '/security/mfa/backup-codes',
        name: 'mfa_backup_codes',
        methods: ['GET'],
    )]
    public function mfaBackupCodes(
        Request $request,
        #[CurrentUser]
        User|CompanyUser $user,
    ): Response {
        if (!$user->isTotpAuthenticationEnabled()) {
            return $this->redirectToRoute($this->routePrefix . 'security_index');
        }

        $session = $request->getSession();
        $plaintext = $session->get(
            '_mfa_pending_backup_codes',
            [],
        );
        $session->remove('_mfa_pending_backup_codes');

        if ([] === $plaintext) {
            $this->addFlash(
                'warning',
                $this->translator->trans('Backup codes have already been shown. Regenerate to get a new set.'),
            );

            return $this->redirectToRoute($this->routePrefix . 'security_index');
        }

        return $this->render(
            'user/security/mfa/backup-codes.html.twig',
            [
                'backupCodes' => $plaintext,
                'routePrefix' => $this->routePrefix,
            ],
        );
    }

    #[IsGranted('SUDO')]
    #[IsCsrfTokenValid(
        id: 'mfa_regenerate_backup_codes',
        tokenKey: '_csrf_token',
    )]
    #[Route(
        path: '/security/mfa/backup-codes/regenerate',
        name: 'mfa_regenerate_backup_codes',
        methods: ['POST'],
    )]
    public function mfaRegenerateBackupCodes(
        Request $request,
        EntityManagerInterface $em,
        BackupCodeManager $backupCodeManager,
        #[CurrentUser]
        User|CompanyUser $user,
    ): Response {
        if (!$user->isTotpAuthenticationEnabled()) {
            return $this->redirectToRoute($this->routePrefix . 'security_index');
        }

        $user->setForceReloginAt(new DateTime());
        $em->flush();

        $plaintext = $backupCodeManager->generateAndStore($user);
        $request->getSession()->set(
            '_mfa_pending_backup_codes',
            $plaintext,
        );

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('Backup codes regenerated. The previous codes are no longer valid.'),
        );

        return $this->redirectToRoute($this->routePrefix . 'mfa_backup_codes');
    }

    #[IsGranted('SUDO')]
    #[IsCsrfTokenValid(
        id: 'mfa_disable',
        tokenKey: '_csrf_token',
    )]
    #[Route(
        path: '/security/mfa/disable',
        name: 'mfa_disable',
        methods: ['POST'],
    )]
    public function mfaDisable(
        EntityManagerInterface $em,
        MfaPolicy $mfaPolicy,
        #[CurrentUser]
        User|CompanyUser $user,
    ): Response {
        if (
            $user instanceof User
            && $mfaPolicy->isRequiredFor($user)
        ) {
            $this->addFlash(
                AlertTypes::Danger->value,
                $this->translator->trans(
                    'Multi-factor authentication cannot be disabled while it is required for your role.',
                ),
            );

            return $this->redirectToRoute($this->routePrefix . 'security_index');
        }

        $user->setTotpSecret(null);
        $user->setBackupCodeSlots(null);
        $em->flush();

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('MFA disabled.'),
        );

        return $this->redirectToRoute($this->routePrefix . 'security_index');
    }

    /**
     * Options to pass to {@see PasswordResetRequestFormType}. Defaults to "require membership number".
     *
     * @return array<string, mixed>
     */
    protected function passwordResetRequestFormOptions(): array
    {
        return [];
    }

    /**
     * Build the per-credential rate-limiter key for a password-reset request. Subclasses combine the form fields
     * that uniquely identify the requester (e.g. email + membership number, or just email).
     *
     * @param FormInterface<array<string, mixed>> $form
     */
    abstract protected function buildPasswordResetCredentialKey(FormInterface $form): string;

    /**
     * Build the messenger message dispatched to the worker when a password-reset request is rate-accepted.
     *
     * @param FormInterface<array<string, mixed>> $form
     */
    abstract protected function buildPasswordResetMessage(FormInterface $form): PasswordResetRequestEmail;

    /**
     * Per-firewall session key used to bind a verified {@see PasswordReset} id to the browser session between the
     * temp-hash GET and the password-submission POST. Must differ between firewalls to prevent cross-firewall
     * resumption attacks via a shared PHP session.
     */
    abstract protected function passwordResetSessionKey(): string;

    /**
     * Resolve the password-target entity for a verified {@see PasswordReset}. Return `null` if the underlying
     * relationship is missing (treated as a stale link). For the User firewall this is the {@see User} entity,
     * instantiated on first activation if absent; for the CompanyUser firewall it is the {@see CompanyUser} entity
     * directly.
     */
    abstract protected function resolvePasswordResetTarget(
        PasswordReset $passwordReset,
    ): User|CompanyUser|null;

    /**
     * Delete every {@see PasswordReset} row associated with the target of the given (successful) reset. Burns the
     * just-used token along with any other outstanding tokens for the same user, so a stolen older link cannot be
     * replayed.
     */
    abstract protected function deletePasswordResetsForTarget(PasswordReset $passwordReset): void;

    /**
     * @param FormInterface<mixed>|null $form Bound to `User|CompanyUser` data when in stage-2, null before that.
     */
    private function renderPasswordResetForm(?FormInterface $form = null): Response
    {
        $form ??= $this->createForm(SetPasswordFormType::class);

        return $this->render(
            'user/password-reset.html.twig',
            [
                'form' => $form,
                'type' => $this->userType,
            ],
        );
    }

    private function staleLinkRedirect(): RedirectResponse
    {
        $this->addFlash(
            AlertTypes::Warning->value,
            $this->translator->trans(
                'Your password-reset link has expired or has already been used. Please request a new one.',
            ),
        );

        return $this->redirectToRoute($this->routePrefix . 'forgot_password');
    }

    /**
     * Stage-1 and stage-2 responses must not leak the URL (with its `?th=...` query) to caches or history snapshots on
     * third-party resources loaded by the rendered page.
     */
    private function withNoLeakHeaders(Response $response): Response
    {
        $response->headers->set(
            'Referrer-Policy',
            'no-referrer',
        );
        $response->headers->set(
            'Cache-Control',
            'no-store, no-cache, must-revalidate, private',
        );
        $response->headers->set(
            'Pragma',
            'no-cache',
        );

        return $response;
    }

    /**
     * @param FormInterface<array<string, mixed>> $form
     */
    private function renderSudoConfirm(
        FormInterface $form,
        string $sudoConfirmRouteName,
        string $next,
    ): Response {
        return $this->render(
            'user/sudo-confirm.html.twig',
            [
                'form' => $form,
                'sudoConfirmRoute' => $sudoConfirmRouteName,
                'next' => $next,
            ],
        );
    }

    /**
     * Accepts only same-origin paths (`/...` but not `//...`). Anything else collapses to the site root, to prevent
     * open-redirect via `?next=`.
     */
    private function safeNextUrl(string $next): string
    {
        if (
            '' === $next
            || !str_starts_with(
                $next,
                '/',
            )
            || str_starts_with(
                $next,
                '//',
            )
        ) {
            return '/';
        }

        return $next;
    }

    private function firewall(Request $request): string
    {
        return $this->firewallMap->getFirewallConfig($request)?->getName()
            ?? throw new LogicException('No firewall matched the request.');
    }
}
