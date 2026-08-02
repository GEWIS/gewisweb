<?php

declare(strict_types=1);

namespace App\Controller\Decision;

use App\Entity\Decision\Enums\MeetingTypes;
use App\Entity\Decision\MeetingDocumentVersion;
use App\Entity\Decision\MeetingMinutesVersion;
use App\Entity\Decision\ReferenceDocumentVersion;
use App\Entity\User\Enums\UserRoles;
use App\Repository\Decision\MeetingRepository;
use App\Service\Application\FileDownloadHelper;
use App\Service\Decision\MeetingDocumentZipBuilder;
use App\Service\Decision\MeetingQueryService;
use NumberFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function sprintf;
use function strtoupper;

#[IsGranted(
    attribute: UserRoles::User->value,
    message: 'You are not allowed to view meetings.',
)]
#[Route(
    path: '/meetings',
    name: 'meetings/',
)]
class MeetingController extends AbstractController
{
    public function __construct(
        private readonly MeetingQueryService $meetingQueryService,
        private readonly MeetingRepository $meetingRepository,
        private readonly MeetingDocumentZipBuilder $zipBuilder,
        private readonly FileDownloadHelper $fileDownloadHelper,
    ) {
    }

    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        return $this->render('decision/meetings/index.html.twig');
    }

    #[Route(
        path: '/search',
        name: 'search',
    )]
    public function search(): Response
    {
        return $this->render('decision/meetings/search.html.twig');
    }

    #[Route(
        path: '/{type}/{number}',
        name: 'view',
        requirements: [
            'type' => 'gmm|bm|cm|virt',
            'number' => '\d+',
        ],
    )]
    public function view(
        Request $request,
        string $type,
        int $number,
    ): Response {
        $view = $this->meetingQueryService->getMeetingView(
            MeetingTypes::tryFromSearch(strtoupper($type)),
            $number,
        );

        if (null === $view) {
            throw $this->createNotFoundException();
        }

        $formatter = new NumberFormatter(
            $request->getLocale(),
            NumberFormatter::ORDINAL,
        );

        return $this->render(
            'decision/meetings/view.html.twig',
            [
                'view' => $view,
                'ordinalNumber' => $formatter->format($number),
                'nearby' => $this->meetingQueryService->getNearbyMeetings($view->meeting),
            ],
        );
    }

    #[Route(
        path: '/{type}/{number}/documents.zip',
        name: 'documents_zip',
        requirements: [
            'type' => 'gmm|bm|cm|virt',
            'number' => '\d+',
        ],
        methods: ['GET'],
    )]
    public function downloadAllDocuments(
        string $type,
        int $number,
    ): Response {
        $meeting = $this->meetingRepository->find([
            'type' => MeetingTypes::tryFromSearch(strtoupper($type)),
            'number' => $number,
        ]);
        if (null === $meeting) {
            throw $this->createNotFoundException();
        }

        $zipPath = $this->zipBuilder->build($meeting);
        if (null === $zipPath) {
            throw $this->createNotFoundException();
        }

        $response = new BinaryFileResponse($zipPath);
        $response->deleteFileAfterSend();
        $response->headers->set(
            'Content-Type',
            'application/zip',
        );
        $response->headers->set(
            'Content-Disposition',
            HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                sprintf(
                    '%s %d documents.zip',
                    strtoupper($type),
                    $number,
                ),
            ),
        );

        return $response;
    }

    #[Route(
        path: '/documents/{version}/download',
        name: 'document_download',
        requirements: ['version' => '\d+'],
        methods: ['GET'],
    )]
    public function downloadDocument(MeetingDocumentVersion $version): Response
    {
        return $this->fileDownloadHelper->download(
            $version->getPath(),
            sprintf(
                '%s (%s).pdf',
                $version->getDocument()->getName(),
                $version->getVersionLabel(),
            ),
            'application/pdf',
        );
    }

    #[Route(
        path: '/minutes/{version}/download',
        name: 'minutes_download',
        requirements: ['version' => '\d+'],
        methods: ['GET'],
    )]
    public function downloadMinutes(MeetingMinutesVersion $version): Response
    {
        $meeting = $version->getMinutes()->getMeeting();

        return $this->fileDownloadHelper->download(
            $version->getPath(),
            sprintf(
                '%s %d minutes (%s).pdf',
                $meeting->getType()->value,
                $meeting->getNumber(),
                $version->getVersionLabel(),
            ),
            'application/pdf',
        );
    }

    #[Route(
        path: '/reference/{version}/download',
        name: 'reference_download',
        requirements: ['version' => '\d+'],
        methods: ['GET'],
    )]
    public function downloadReference(ReferenceDocumentVersion $version): Response
    {
        return $this->fileDownloadHelper->download(
            $version->getPath(),
            sprintf(
                '%s (%s).pdf',
                $version->getReferenceDocument()->getName(),
                $version->getVersionLabel(),
            ),
            'application/pdf',
        );
    }
}
