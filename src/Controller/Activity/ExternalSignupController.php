<?php

declare(strict_types=1);

namespace App\Controller\Activity;

use App\Entity\Activity\Enums\ExternalSignupVerificationPurpose;
use App\Entity\Application\Enums\AlertTypes;
use App\Service\Activity\ExternalSignupTokenResolver;
use App\Service\Activity\SignupManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Public, login-less self-service for external (non-member) sign-ups, reached through a signed email token (the
 * sign-up itself is created by {@see ActivityController::externalSignup}):
 *  - {@see self::verify()} confirms a freshly-created sign-up (double opt-in) and issues the manage link;
 *  - {@see self::manage()} renders the self-service page; the edit and unsubscribe are live actions on the
 *    {@see \App\Twig\Components\Activity\ExternalSignupManage} component, which re-validates the token every request.
 *
 * The token (`selector.verifier`) is looked up by selector and hash-compared, so a leaked selector alone is useless;
 * any failure is a 404 to avoid confirming which tokens exist.
 */
#[Route(
    path: '/activities/signup',
    name: 'activity/external_signup_',
)]
class ExternalSignupController extends AbstractController
{
    private const string TOKEN_REQUIREMENT = '[0-9a-f]{32}\.[0-9a-f]{64}';

    public function __construct(
        private readonly ExternalSignupTokenResolver $tokenResolver,
        private readonly SignupManager $signupManager,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Show the double opt-in confirmation page for a freshly-created external sign-up. This is a pure read: it only
     * resolves the token (consumption lives in {@see SignupManager::confirmExternalSignup}), so email clients and link
     * scanners that fetch the URL do not accidentally confirm the sign-up. The actual confirmation is a POST from the
     * form on this page ({@see self::confirm()}).
     */
    #[Route(
        path: '/verify/{token}',
        name: 'verify',
        requirements: ['token' => self::TOKEN_REQUIREMENT],
        methods: ['GET'],
    )]
    public function verify(string $token): Response
    {
        $verification = $this->tokenResolver->resolve(
            $token,
            ExternalSignupVerificationPurpose::Verify,
        );
        if (null === $verification) {
            throw $this->createNotFoundException();
        }

        $signupList = $verification->getExternalSignup()->getSignupList();

        return $this->render(
            'activity/external-signup-verify.html.twig',
            [
                'token' => $token,
                'activity' => $signupList->getActivity(),
                'signupList' => $signupList,
            ],
        );
    }

    /**
     * Confirm the external sign-up (double opt-in): consumes the verification token and issues the manage link. Reached
     * only by submitting the form on the {@see self::verify()} page, so a link scanner's GET never confirms.
     */
    #[Route(
        path: '/verify/{token}',
        name: 'confirm',
        requirements: ['token' => self::TOKEN_REQUIREMENT],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: 'external_signup_verify',
        tokenKey: '_csrf_token',
    )]
    public function confirm(string $token): Response
    {
        $verification = $this->tokenResolver->resolve(
            $token,
            ExternalSignupVerificationPurpose::Verify,
        );
        if (null === $verification) {
            throw $this->createNotFoundException();
        }

        $activity = $verification->getExternalSignup()->getSignupList()->getActivity();

        // A cancelled or unpublished activity has all sign-up interaction frozen: do not confirm, and send the visitor
        // somewhere sensible (an unpublished activity's own page 404s, so fall back to the overview there).
        if ($activity->isFrozen()) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans('This activity is no longer open for sign-ups, so it cannot be confirmed.'),
            );

            if ($activity->isUnpublished()) {
                return $this->redirectToRoute('activity/index');
            }

            return $this->redirectToRoute(
                'activity/view',
                ['activity' => $activity->getId()],
            );
        }

        $this->signupManager->confirmExternalSignup($verification);

        $this->addFlash(
            'success',
            $this->translator->trans('Your sign-up is confirmed! We have emailed you a link to manage it.'),
        );

        return $this->redirectToRoute(
            'activity/view',
            ['activity' => $activity->getId()],
        );
    }

    #[Route(
        path: '/manage/{token}',
        name: 'manage',
        requirements: ['token' => self::TOKEN_REQUIREMENT],
        methods: ['GET'],
    )]
    public function manage(string $token): Response
    {
        $verification = $this->tokenResolver->resolve(
            $token,
            ExternalSignupVerificationPurpose::Manage,
        );
        if (null === $verification) {
            throw $this->createNotFoundException();
        }

        return $this->render(
            'activity/external-signup-manage.html.twig',
            [
                'token' => $token,
                'activity' => $verification->getExternalSignup()->getSignupList()->getActivity(),
            ],
        );
    }
}
