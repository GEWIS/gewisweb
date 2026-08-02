<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\User\CompanyUserInvite;
use App\Form\User\CompanyUserInviteAcceptType;
use App\Repository\User\CompanyUserInviteRepository;
use App\Security\User\Firewall;
use App\Security\User\HandlerRegistry;
use App\Service\User\CompanyUserAccessPolicy;
use App\Service\User\CompanyUserInviteService;
use App\Util\Application\SplitToken;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Where an invited representative turns their invitation into an account. Reachable without signing in, which is the
 * point: no account exists yet.
 */
#[IsGranted('PUBLIC_ACCESS')]
#[Route(
    path: '/company/invite',
    name: 'company_user_invite_',
)]
class CompanyInviteController extends AbstractController
{
    public function __construct(
        private readonly CompanyUserInviteRepository $inviteRepository,
        private readonly CompanyUserInviteService $inviteService,
        private readonly CompanyUserAccessPolicy $accessPolicy,
        private readonly HandlerRegistry $handlerRegistry,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route(
        path: '/{token}',
        name: 'accept',
        requirements: ['token' => '[0-9a-f]{32}\.[0-9a-f]{64}'],
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function accept(
        string $token,
        Request $request,
        Security $security,
    ): Response {
        $invite = $this->resolve($token);
        if (null === $invite) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans(
                    'This invitation is no longer valid. Ask your contact at GEWIS for a new one.',
                ),
            );

            return $this->redirectToRoute('company_user_login');
        }

        $form = $this->createForm(CompanyUserInviteAcceptType::class)->handleRequest($request);

        if (
            !$form->isSubmitted()
            || !$form->isValid()
        ) {
            return $this->render(
                'career/company/invite-accept.html.twig',
                [
                    'form' => $form,
                    'invite' => $invite,
                ],
            );
        }

        $companyUser = $this->inviteService->accept(
            $invite,
            $form->get('plainPassword')->getData(),
        );

        // The account is theirs from here on, but the portal is closed to a company without a running contract, so
        // signing them in would only be undone by the access guard on the next request. Say so instead; the password
        // they just chose works as soon as the board enters the package.
        if (
            !$this->accessPolicy->isAllowed(
                $companyUser,
                new DateTimeImmutable('now'),
            )
        ) {
            $this->addFlash(
                AlertTypes::Warning->value,
                $this->translator->trans(
                    'Your account is ready, but your company has no running contract with GEWIS. You can sign in once the board has entered it.', // phpcs:ignore Generic.Files.LineLength.TooLong -- user-visible strings should not be split
                ),
            );

            return $this->redirectToRoute('company_user_login');
        }

        // They have just proven they hold the address and chosen a password, so sending them to a login form to type
        // it again establishes nothing. Signing in programmatically skips the remember-me handler that a form login
        // goes through, and a session without its managed-session row is torn down again on the very next request, so
        // the cookie is minted here by hand.
        $security->login(
            $companyUser,
            'form_login',
            Firewall::Company->value,
        );
        $this->handlerRegistry->get(Firewall::Company->value)?->createRememberMeCookie($companyUser);

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('Welcome. Your account is ready.'),
        );

        return $this->redirectToRoute('company/index');
    }

    private function resolve(string $token): ?CompanyUserInvite
    {
        $split = SplitToken::split($token);
        if (null === $split) {
            return null;
        }

        $invite = $this->inviteRepository->findBySelector($split['selector']);
        if (
            null === $invite
            || $invite->isExpired()
            || !SplitToken::matches(
                $invite->getHashedToken(),
                $split['verifier'],
                CompanyUserInvite::HASH_ALGO,
            )
        ) {
            return null;
        }

        return $invite;
    }
}
