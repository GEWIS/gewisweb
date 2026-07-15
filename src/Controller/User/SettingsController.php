<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Form\User\SettingsFormType;
use App\Repository\User\UserSettingsRepository;
use App\Security\User\SudoMode;
use App\Service\Photo\MemberTagPurgeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The member-facing settings/privacy page. Member-only, so it does not share the
 * {@see AbstractSecurityController} base (which also serves company users and is gated behind sudo re-auth) - these
 * are low-risk preferences that only need the member to be logged in.
 */
#[IsGranted(
    attribute: UserRoles::User->value,
    message: 'You are not allowed to change these settings.',
)]
#[Route(
    path: '/user/settings',
    name: 'user_settings_',
)]
class SettingsController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly UserSettingsRepository $settingsRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route(
        path: '',
        name: 'index',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function settings(
        Request $request,
        SudoMode $sudoMode,
        #[CurrentUser]
        User $user,
    ): Response {
        $settings = $this->settingsRepository->getOrCreateForUser($user);
        $form = $this->createForm(
            SettingsFormType::class,
            $settings,
        )->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            $this->entityManager->flush();

            $this->addFlash(
                AlertTypes::Success->value,
                $this->translator->trans('Your settings have been saved.'),
            );

            return $this->redirectToRoute('user_settings_index');
        }

        return $this->render(
            'user/settings/index.html.twig',
            [
                'form' => $form,
                // Removing tags requires sudo. The template opens the confirmation modal when sudo is active, and
                // otherwise routes through re-authentication (returning here) so the user is never dumped elsewhere.
                'sudoActive' => $sudoMode->isActive(),
            ],
        );
    }

    /**
     * Persist the cosmetics preference from the navbar switch. Called via `fetch` by the `cosmetics-toggle` Stimulus
     * controller, so it just flips the flag and returns no content.
     */
    #[IsCsrfTokenValid(
        id: 'cosmetics',
        tokenKey: '_csrf_token',
    )]
    #[Route(
        path: '/cosmetics',
        name: 'cosmetics',
        methods: ['POST'],
    )]
    public function cosmetics(
        Request $request,
        #[CurrentUser]
        User $user,
    ): Response {
        $settings = $this->settingsRepository->getOrCreateForUser($user);
        $settings->setDisableCosmetics($request->request->getBoolean('disabled'));
        $this->entityManager->flush();

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * Remove all existing photo tags of the current member (the retroactive counterpart to the tagging opt-out). This
     * is irreversible, so it requires sudo (recent re-authentication), like the other destructive account actions.
     */
    #[IsGranted('SUDO')]
    #[IsCsrfTokenValid(
        id: 'purge_tags',
        tokenKey: '_csrf_token',
    )]
    #[Route(
        path: '/purge-tags',
        name: 'purge_tags',
        methods: ['POST'],
    )]
    public function purgeTags(
        MemberTagPurgeService $purgeService,
        #[CurrentUser]
        User $user,
    ): Response {
        $purgeService->purgeTagsOf($user->getMember());

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans('All photo tags of you have been removed.'),
        );

        return $this->redirectToRoute('user_settings_index');
    }
}
