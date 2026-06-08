<?php

declare(strict_types=1);

namespace App\Controller\Activity;

use App\Entity\Activity\Enums\ExternalSignupVerificationPurpose;
use App\Service\Activity\ExternalSignupTokenResolver;
use App\Service\Activity\SignupManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Public, login-less self-service for external (non-member) sign-ups, reached through a signed e-mail token (the
 * sign-up itself is created by {@see ActivityController::externalSignup}):
 *  - {@see self::verify()} confirms a freshly-created sign-up (double opt-in) and issues the manage link;
 *  - {@see self::manage()} renders the self-service page — the edit and unsubscribe are live actions on the
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

        $activityId = $verification->getExternalSignup()->getSignupList()->getActivity()->getId();
        $this->signupManager->confirmExternalSignup($verification);

        $this->addFlash(
            'success',
            $this->translator->trans('Your sign-up is confirmed! We have e-mailed you a link to manage it.'),
        );

        return $this->redirectToRoute(
            'activity/view',
            ['activity' => $activityId],
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
