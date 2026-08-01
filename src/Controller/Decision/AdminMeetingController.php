<?php

declare(strict_types=1);

namespace App\Controller\Decision;

use App\Entity\Decision\Enums\MeetingTypes;
use App\Entity\Decision\Meeting;
use App\Entity\Decision\MeetingDocument;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\Decision\MeetingPointRepository;
use App\Repository\Decision\MeetingRepository;
use App\Security\User\SudoVoter;
use App\Service\Application\FileStorageException;
use App\Service\Decision\MeetingDocumentService;
use App\Service\Decision\MeetingMinutesService;
use App\Service\Decision\VersionLabelSuggester;
use App\ViewModel\Decision\MeetingOverviewRow;
use App\ViewModel\Decision\MeetingStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_map;
use function assert;
use function ceil;
use function in_array;
use function max;
use function mb_strlen;
use function strtoupper;
use function trim;

/**
 * Board management of meetings: the manageable-meetings index, the per-meeting management page, and the XHR upload
 * endpoints its dropzones post to. Everything else on the management page goes through the
 * {@see \App\Twig\Components\Decision\Admin\MeetingManage} live component.
 *
 * The upload endpoints deliberately carry no CSRF token: they are not form submits but XHR calls, and access is
 * guarded by the class-level board requirement (the photo upload endpoint set this precedent).
 */
#[IsGranted(UserRoles::Board->value)]
#[IsGranted(SudoVoter::ATTRIBUTE)]
#[Route(
    path: '/admin/meetings',
    name: 'admin/meetings/',
)]
class AdminMeetingController extends AbstractController
{
    private const int PAGE_SIZE = 25;

    private const array MANAGEABLE_TYPE_TOKENS = [
        'gmm',
        'bm',
        'cm',
    ];

    public function __construct(
        private readonly MeetingRepository $meetingRepository,
        private readonly MeetingPointRepository $meetingPointRepository,
        private readonly MeetingDocumentService $meetingDocumentService,
        private readonly MeetingMinutesService $meetingMinutesService,
        private readonly VersionLabelSuggester $versionLabelSuggester,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(
        #[MapQueryParameter]
        ?string $type = null,
        #[MapQueryParameter]
        int $page = 1,
    ): Response {
        $typeFilter = null;
        if (
            null !== $type
            && in_array(
                $type,
                self::MANAGEABLE_TYPE_TOKENS,
                true,
            )
        ) {
            $typeFilter = MeetingTypes::tryFromSearch(strtoupper($type));
        }

        $page = max(
            1,
            $page,
        );
        $result = $this->meetingRepository->paginateForOverview(
            $typeFilter,
            null,
            $page,
            self::PAGE_SIZE,
            excludeVirtual: true,
        );

        $rows = array_map(
            static fn (array $item): MeetingOverviewRow => new MeetingOverviewRow(
                $item[0],
                $item[1],
                $item[2] > 0,
                MeetingStatus::derive(
                    $item[0]->getDate(),
                    $item[1] > 0,
                    $item[2] > 0,
                ),
            ),
            $result['items'],
        );

        return $this->render(
            'decision/admin/meetings/index.html.twig',
            [
                'rows' => $rows,
                'type' => $typeFilter?->urlToken(),
                'currentPage' => $page,
                'totalPages' => max(
                    1,
                    (int) ceil($result['total'] / self::PAGE_SIZE),
                ),
                'totalCount' => $result['total'],
                'typeTokens' => self::MANAGEABLE_TYPE_TOKENS,
            ],
        );
    }

    #[Route(
        path: '/{type}/{number}',
        name: 'view',
        requirements: [
            'type' => 'gmm|bm|cm',
            'number' => '\d+',
        ],
    )]
    public function view(
        string $type,
        int $number,
    ): Response {
        $meeting = $this->resolveMeeting(
            $type,
            $number,
        );

        return $this->render(
            'decision/admin/meetings/view.html.twig',
            [
                'meeting' => $meeting,
                'type' => $type,
                'number' => $number,
            ],
        );
    }

    #[Route(
        path: '/{type}/{number}/documents/upload',
        name: 'document_upload',
        requirements: [
            'type' => 'gmm|bm|cm',
            'number' => '\d+',
        ],
        methods: ['POST'],
    )]
    public function uploadDocument(
        Request $request,
        string $type,
        int $number,
    ): JsonResponse {
        $meeting = $this->resolveMeeting(
            $type,
            $number,
        );

        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            return $this->uploadError($this->translator->trans('No file was uploaded.'));
        }

        $name = trim($request->request->getString('name'));
        if (
            mb_strlen($name) < 2
            || mb_strlen($name) > 128
        ) {
            return $this->uploadError(
                $this->translator->trans('The document name must be between 2 and 128 characters long.'),
            );
        }

        $point = null;
        $pointId = $request->request->getInt('point');
        if ($pointId > 0) {
            $point = $this->meetingPointRepository->find($pointId);

            if ($point?->getMeeting() !== $meeting) {
                return $this->uploadError(
                    $this->translator->trans('The agenda point does not belong to this meeting.'),
                );
            }
        }

        try {
            $this->meetingDocumentService->uploadDocument(
                $meeting,
                $point,
                $name,
                $file,
                $this->versionLabel($request),
                $this->actor(),
            );
        } catch (FileStorageException $exception) {
            return $this->uploadError($exception->getMessage());
        }

        return new JsonResponse(['ok' => true]);
    }

    #[Route(
        path: '/documents/{document}/versions',
        name: 'document_version_upload',
        requirements: ['document' => '\d+'],
        methods: ['POST'],
    )]
    public function uploadDocumentVersion(
        Request $request,
        MeetingDocument $document,
    ): JsonResponse {
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            return $this->uploadError($this->translator->trans('No file was uploaded.'));
        }

        try {
            $this->meetingDocumentService->uploadVersion(
                $document,
                $file,
                $this->versionLabel($request),
                $this->actor(),
            );
        } catch (FileStorageException $exception) {
            return $this->uploadError($exception->getMessage());
        }

        return new JsonResponse(['ok' => true]);
    }

    #[Route(
        path: '/{type}/{number}/minutes',
        name: 'minutes_upload',
        requirements: [
            'type' => 'gmm|bm|cm',
            'number' => '\d+',
        ],
        methods: ['POST'],
    )]
    public function uploadMinutes(
        Request $request,
        string $type,
        int $number,
    ): JsonResponse {
        $meeting = $this->resolveMeeting(
            $type,
            $number,
        );

        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            return $this->uploadError($this->translator->trans('No file was uploaded.'));
        }

        try {
            $this->meetingMinutesService->uploadMinutes(
                $meeting,
                $file,
                $this->versionLabel($request),
                $this->actor(),
            );
        } catch (FileStorageException $exception) {
            return $this->uploadError($exception->getMessage());
        }

        return new JsonResponse(['ok' => true]);
    }

    private function resolveMeeting(
        string $type,
        int $number,
    ): Meeting {
        $meeting = $this->meetingRepository->findMeeting(
            MeetingTypes::tryFromSearch(strtoupper($type)),
            $number,
        );

        if (null === $meeting) {
            throw $this->createNotFoundException();
        }

        return $meeting;
    }

    private function versionLabel(Request $request): string
    {
        $label = trim($request->request->getString('versionLabel'));

        return '' === $label
            ? $this->versionLabelSuggester->suggest(null)
            : $label;
    }

    private function actor(): User
    {
        $user = $this->getUser();
        assert($user instanceof User);

        return $user;
    }

    private function uploadError(string $message): JsonResponse
    {
        return new JsonResponse(
            ['error' => $message],
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }
}
