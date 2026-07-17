<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Entity\User\CompanyUser;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\Enums\UserTypes;
use App\Entity\User\ExternalApp;
use App\Entity\User\PasswordReset;
use App\Entity\User\User;
use App\Form\User\ExternalAppAuthorisationType;
use App\Message\User\PasswordResetRequestEmail;
use App\Repository\User\ExternalAppAuthenticationRepository;
use App\Repository\User\ExternalAppRepository;
use App\Repository\User\PasswordResetRepository;
use App\Repository\User\UserRepository;
use App\Security\User\HandlerRegistry;
use App\Service\User\ExternalAppService;
use App\Service\User\SessionManager;
use DateTimeImmutable;
use Override;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

use function assert;
use function bin2hex;
use function intval;
use function sprintf;

#[Route(
    path: '/user',
    name: 'user_',
)]
class UserController extends AbstractSecurityController
{
    public function __construct(
        TranslatorInterface $translator,
        SessionManager $sessionManager,
        HandlerRegistry $registry,
        #[Autowire(service: 'security.firewall.map')]
        FirewallMap $firewallMap,
        private readonly UserRepository $userRepository,
        private readonly PasswordResetRepository $passwordResetRepository,
        private readonly ExternalAppRepository $externalAppRepository,
        private readonly ExternalAppAuthenticationRepository $externalAppAuthenticationRepository,
        private readonly ExternalAppService $externalAppService,
    ) {
        parent::__construct(
            $translator,
            $sessionManager,
            $registry,
            $firewallMap,
            routePrefix: 'user_',
            userType: UserTypes::User,
        );
    }

    /**
     * Let an external application authenticate as the member: after the member consents, mint a short-lived signed
     * token and send the browser back to the application's callback with it.
     *
     * Routed from {@see /config/routes.yaml} (`user_token`) rather than via an attribute, so it stays a plain locale-
     * less URL.
     */
    public function token(
        Request $request,
        string $app,
    ): Response {
        $this->denyAccessUnlessGranted(UserRoles::User->value);
        $user = $this->getUser();
        assert($user instanceof User);

        $externalApp = $this->externalAppRepository->findByAppId($app);
        // A disabled or expired application must not be able to mint any more tokens.
        if (
            null === $externalApp
            || !$externalApp->isActive()
        ) {
            throw $this->createNotFoundException();
        }

        // If the member authorised this application within the last 90 days, authenticate straight away; if it was
        // longer ago, show a lighter reminder rather than the full prompt.
        $reminder = false;
        $lastAuthentication = $this->externalAppAuthenticationRepository->getLastAuthentication(
            $user,
            $externalApp,
        );
        if (null !== $lastAuthentication) {
            if (new DateTimeImmutable()->diff($lastAuthentication->getTime())->days <= 90) {
                return $this->renderTokenRedirect(
                    $externalApp,
                    $this->externalAppService->callbackWithToken(
                        $externalApp,
                        $user,
                    ),
                );
            }

            $reminder = true;
        }

        $form = $this->createForm(
            ExternalAppAuthorisationType::class,
            options: ['reminder' => $reminder],
        );
        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            // The reminder variant only has a continue button, so a submission there is always a "yes".
            $declined = false;
            if (!$reminder) {
                $cancel = $form->get('cancel');
                $declined = $cancel instanceof ClickableInterface && $cancel->isClicked();
            }

            // A declining member is sent back to the application without a token.
            $url = $declined
                ? $externalApp->getUrl()
                : $this->externalAppService->callbackWithToken(
                    $externalApp,
                    $user,
                );

            return $this->renderTokenRedirect(
                $externalApp,
                $url,
            );
        }

        return $this->render(
            'user/token.html.twig',
            [
                'appId' => $app,
                'claims' => $externalApp->getClaims(),
                'form' => $form,
                'reminder' => $reminder,
            ],
        );
    }

    /**
     * A `Location:` redirect to the external callback after a POST is a `form-action` CSP violation in Chromium, so the
     * browser is sent on with a document-level meta refresh instead.
     */
    private function renderTokenRedirect(
        ExternalApp $externalApp,
        string $url,
    ): Response {
        return $this->render(
            'user/token-redirect.html.twig',
            [
                'appId' => $externalApp->getAppId(),
                'url' => $url,
            ],
        );
    }

    /**
     * @param FormInterface<array<string, mixed>> $form
     */
    #[Override]
    protected function buildPasswordResetCredentialKey(FormInterface $form): string
    {
        return bin2hex(sprintf(
            '%s-%s',
            $form->get('membershipNumber')->getData(),
            $form->get('email')->getData(),
        ));
    }

    /**
     * @param FormInterface<array<string, mixed>> $form
     */
    #[Override]
    protected function buildPasswordResetMessage(FormInterface $form): PasswordResetRequestEmail
    {
        return new PasswordResetRequestEmail(
            UserTypes::User,
            $form->get('email')->getData(),
            intval($form->get('membershipNumber')->getData()),
        );
    }

    #[Override]
    protected function passwordResetSessionKey(): string
    {
        return '_pwr_id_user';
    }

    #[Override]
    protected function resolvePasswordResetTarget(
        PasswordReset $passwordReset,
    ): User|CompanyUser|null {
        $member = $passwordReset->getMember();
        if (null === $member) {
            return null;
        }

        // Instantiate a User on first activation (Member exists from GEWISDB sync, no User row yet). The new row is
        // only persisted by the abstract base after a successful, validated password submission.
        $user = $this->userRepository->find($member->getLidnr());
        if (null === $user) {
            $user = new User();
            $user->setLidnr($member->getLidnr());
            $user->setMember($member);
        }

        return $user;
    }

    #[Override]
    protected function deletePasswordResetsForTarget(PasswordReset $passwordReset): void
    {
        $member = $passwordReset->getMember();
        if (null === $member) {
            return;
        }

        $this->passwordResetRepository->deleteAllForMember($member);
    }
}
