<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\User\DataExportRequest;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Form\User\GeneralSettingsType;
use App\Form\User\PrivacySettingsType;
use App\Message\User\ExportUserDataMessage;
use App\MessageHandler\User\ExportUserDataHandler;
use App\Repository\User\DataExportRequestRepository;
use App\Repository\User\UserSettingsRepository;
use App\Security\User\SudoMode;
use App\Service\Application\FileDownloadHelper;
use App\Service\Application\FileStorage;
use App\Service\Photo\MemberTagPurgeService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
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
        private readonly DataExportRequestRepository $dataExportRequestRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * How long a just-submitted export request keeps the button in its "being prepared" state before a fresh request
     * is allowed again, in case the queued job never produced a file.
     */
    private const string EXPORT_PENDING_WINDOW = '-15 minutes';

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
        FileStorage $fileStorage,
        #[CurrentUser]
        User $user,
    ): Response {
        $settings = $this->settingsRepository->getOrCreateForUser($user);
        $form = $this->createForm(
            PrivacySettingsType::class,
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

        $hasDataExport = ExportUserDataHandler::isAvailable(
            $fileStorage,
            $user->getMember()->getLidnr(),
        );
        // "Being prepared" only while there is no downloadable file yet and a request was made within the window.
        $latestRequest = $this->dataExportRequestRepository->findLatestForUser($user);
        $dataExportPending = !$hasDataExport
            && null !== $latestRequest
            && $latestRequest->getRequestedAt() > new DateTimeImmutable(self::EXPORT_PENDING_WINDOW);

        return $this->render(
            'user/settings/index.html.twig',
            [
                'form' => $form,
                // Removing tags requires sudo. The template opens the confirmation modal when sudo is active, and
                // otherwise routes through re-authentication (returning here) so the user is never dumped elsewhere.
                'sudoActive' => $sudoMode->isActive(),
                'hasDataExport' => $hasDataExport,
                'dataExportPending' => $dataExportPending,
            ],
        );
    }

    #[Route(
        path: '/general',
        name: 'general',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function general(
        Request $request,
        #[CurrentUser]
        User $user,
    ): Response {
        $settings = $this->settingsRepository->getOrCreateForUser($user);
        $form = $this->createForm(
            GeneralSettingsType::class,
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

            return $this->redirectToRoute('user_settings_general');
        }

        return $this->render(
            'user/settings/general.html.twig',
            ['form' => $form],
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

    /**
     * Kick off building the member's data export. It runs asynchronously and the member is emailed a download link
     * when it is ready. Requires sudo, like the other actions that touch the member's full personal data.
     */
    #[IsGranted('SUDO')]
    #[IsCsrfTokenValid(
        id: 'data_export',
        tokenKey: '_csrf_token',
    )]
    #[Route(
        path: '/data-export',
        name: 'data_export_request',
        methods: ['POST'],
    )]
    public function requestDataExport(
        MessageBusInterface $messageBus,
        FileStorage $fileStorage,
        #[CurrentUser]
        User $user,
    ): Response {
        $lidnr = $user->getMember()->getLidnr();

        // Do not queue another job while the member still has a downloadable export, or one is already being prepared.
        if (
            ExportUserDataHandler::isAvailable(
                $fileStorage,
                $lidnr,
            )
        ) {
            $this->addFlash(
                AlertTypes::Info->value,
                $this->translator->trans('You already have a recent data export. You can download it below.'),
            );

            return $this->redirectToRoute('user_settings_index');
        }

        $latestRequest = $this->dataExportRequestRepository->findLatestForUser($user);
        if (
            null !== $latestRequest
            && $latestRequest->getRequestedAt() > new DateTimeImmutable(self::EXPORT_PENDING_WINDOW)
        ) {
            $this->addFlash(
                AlertTypes::Info->value,
                $this->translator->trans('Your data export is already being prepared.'),
            );

            return $this->redirectToRoute('user_settings_index');
        }

        $request = new DataExportRequest();
        $request->setUser($user);
        $request->setRequestedAt(new DateTimeImmutable());
        $this->entityManager->persist($request);
        $this->entityManager->flush();

        $messageBus->dispatch(new ExportUserDataMessage($lidnr));

        $this->addFlash(
            AlertTypes::Success->value,
            $this->translator->trans(
                'We are preparing your data export. You will receive an email with a download link when it is ready.',
            ),
        );

        return $this->redirectToRoute('user_settings_index');
    }

    /**
     * Serve the member their own most recent data export. The stored path is derived from the member's number, so a
     * member can only ever reach their own file. Requires sudo, like the other actions that touch the member's full
     * personal data; being a GET route, the re-authentication returns the member straight back here to the download.
     */
    #[IsGranted('SUDO')]
    #[Route(
        path: '/data-export/download',
        name: 'data_export_download',
        methods: ['GET'],
    )]
    public function downloadDataExport(
        FileStorage $fileStorage,
        FileDownloadHelper $fileDownloadHelper,
        #[CurrentUser]
        User $user,
    ): Response {
        $lidnr = $user->getMember()->getLidnr();
        if (
            !ExportUserDataHandler::isAvailable(
                $fileStorage,
                $lidnr,
            )
        ) {
            throw $this->createNotFoundException();
        }

        return $fileDownloadHelper->download(
            ExportUserDataHandler::exportPath($lidnr),
            'gewis-data-export.json',
            'application/json',
        );
    }
}
