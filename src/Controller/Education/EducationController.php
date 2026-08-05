<?php

declare(strict_types=1);

namespace App\Controller\Education;

use App\Entity\Education\CourseDocument;
use App\Entity\Education\CourseDocumentDownload;
use App\Entity\Education\Enums\DownloadStatus;
use App\Entity\User\User;
use App\Repository\Education\CourseDocumentRepository;
use App\Repository\Education\CourseRepository;
use App\Security\Education\CourseDocumentVoter;
use App\Service\Application\FileDownloadHelper;
use App\Service\Education\CourseDocumentDownloadService;
use App\Service\Education\EducationOverviewCountsProvider;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

use function strtoupper;

/**
 * Browsing is open to everyone, downloading is not: see {@see CourseDocumentVoter}. A visitor who may not download
 * still sees the whole archive, because knowing an exam exists is what tells someone whether logging in or walking to
 * campus is worth it.
 */
#[Route(
    path: '/education',
    name: 'education/',
)]
class EducationController extends AbstractController
{
    /** Course codes are five to nine alphanumerics, e.g. 2IL50 or 2WBB0. */
    private const string COURSE_CODE = '[A-Za-z0-9]{5,9}';

    /** A download is keyed on the unguessable token of its request. */
    private const string TOKEN = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

    private const int RECENT_DOCUMENT_LIMIT = 4;
    private const int TOP_COURSE_LIMIT = 6;

    public function __construct(
        private readonly CourseRepository $courseRepository,
        private readonly CourseDocumentRepository $documentRepository,
        private readonly EducationOverviewCountsProvider $countsProvider,
        private readonly CourseDocumentDownloadService $downloadService,
        private readonly FileDownloadHelper $fileDownloadHelper,
    ) {
    }

    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(): Response
    {
        return $this->render(
            'education/index.html.twig',
            [
                'counts' => $this->countsProvider->counts(),
                'recentDocuments' => $this->documentRepository->findRecent(self::RECENT_DOCUMENT_LIMIT),
                'topCourses' => $this->courseRepository->findWithMostDocuments(self::TOP_COURSE_LIMIT),
            ],
        );
    }

    #[Route(
        path: '/courses',
        name: 'courses',
    )]
    public function courses(): Response
    {
        return $this->render('education/courses.html.twig');
    }

    #[Route(
        path: '/course/{code}',
        name: 'course',
        requirements: ['code' => self::COURSE_CODE],
    )]
    public function course(string $code): Response
    {
        $course = $this->courseRepository->findWithDocuments(strtoupper($code));
        if (null === $course) {
            throw $this->createNotFoundException();
        }

        return $this->render(
            'education/course.html.twig',
            ['course' => $course],
        );
    }

    /**
     * Nothing is built here: the watermark has to be composited onto every page, which is not work to do while a
     * browser waits on an open connection.
     */
    #[Route(
        path: '/document/{document}/download',
        name: 'document_request',
        requirements: ['document' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        id: 'download-course-document',
        tokenKey: '_csrf_token',
    )]
    public function requestDownload(
        Request $request,
        CourseDocument $document,
        #[CurrentUser]
        ?User $user,
    ): Response {
        $this->denyAccessUnlessGranted(
            CourseDocumentVoter::DOWNLOAD,
            $document,
        );

        $download = $this->downloadService->request(
            $document,
            $user,
            $request->getClientIp(),
        );

        return $this->redirectToRoute(
            'education/download',
            ['token' => $download->getToken()->toRfc4122()],
        );
    }

    /**
     * Polls {@see downloadStatus()} and follows on when the file is ready.
     */
    #[Route(
        path: '/download/{token}',
        name: 'download',
        requirements: ['token' => self::TOKEN],
    )]
    public function download(
        Request $request,
        #[MapEntity(mapping: ['token' => 'token'])]
        CourseDocumentDownload $download,
        #[CurrentUser]
        ?User $user,
    ): Response {
        $this->denyUnlessCollectable(
            $download,
            $user,
            $request,
        );

        return $this->render(
            'education/download.html.twig',
            ['download' => $download],
        );
    }

    #[Route(
        path: '/download/{token}/status',
        name: 'download_status',
        requirements: ['token' => self::TOKEN],
        methods: ['GET'],
    )]
    public function downloadStatus(
        Request $request,
        #[MapEntity(mapping: ['token' => 'token'])]
        CourseDocumentDownload $download,
        #[CurrentUser]
        ?User $user,
    ): JsonResponse {
        $this->denyUnlessCollectable(
            $download,
            $user,
            $request,
        );

        return $this->json([
            'status' => $download->getStatus()->value,
            'url' => DownloadStatus::Ready === $download->getStatus()
                ? $this->generateUrl(
                    'education/download_file',
                    ['token' => $download->getToken()->toRfc4122()],
                )
                : null,
        ]);
    }

    #[Route(
        path: '/download/{token}/file',
        name: 'download_file',
        requirements: ['token' => self::TOKEN],
        methods: ['GET'],
    )]
    public function downloadFile(
        Request $request,
        #[MapEntity(mapping: ['token' => 'token'])]
        CourseDocumentDownload $download,
        #[CurrentUser]
        ?User $user,
    ): Response {
        $this->denyUnlessCollectable(
            $download,
            $user,
            $request,
        );

        $path = $download->getPath();
        if (
            DownloadStatus::Ready !== $download->getStatus()
            || null === $path
        ) {
            throw $this->createNotFoundException();
        }

        $this->downloadService->markCollected($download);

        return $this->fileDownloadHelper->download(
            $path,
            $this->downloadService->filenameFor($download->getDocument()),
            'application/pdf',
        );
    }

    /**
     * A built file names whoever asked for it, so only they may collect it. The document gate is checked again in case
     * the visitor's standing changed between asking and collecting.
     */
    private function denyUnlessCollectable(
        CourseDocumentDownload $download,
        ?User $user,
        Request $request,
    ): void {
        $this->denyAccessUnlessGranted(
            CourseDocumentVoter::DOWNLOAD,
            $download->getDocument(),
        );

        if (
            $download->isCollectableBy(
                $user,
                $request->getClientIp(),
            )
        ) {
            return;
        }

        throw $this->createAccessDeniedException();
    }
}
