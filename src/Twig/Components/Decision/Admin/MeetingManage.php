<?php

declare(strict_types=1);

namespace App\Twig\Components\Decision\Admin;

use App\Entity\Decision\Enums\MeetingTypes;
use App\Entity\Decision\Meeting;
use App\Entity\Decision\MeetingActivityLog;
use App\Entity\Decision\MeetingDocument;
use App\Entity\Decision\MeetingPoint;
use App\Entity\Decision\MeetingReferenceSelection;
use App\Entity\Decision\ReferenceDocument;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\Decision\MeetingActivityLogRepository;
use App\Repository\Decision\MeetingDocumentRepository;
use App\Repository\Decision\MeetingPointRepository;
use App\Repository\Decision\ReferenceDocumentRepository;
use App\Security\User\SudoVoter;
use App\Service\Decision\MeetingDocumentService;
use App\Service\Decision\MeetingLocalDetailsService;
use App\Service\Decision\MeetingMinutesService;
use App\Service\Decision\MeetingQueryService;
use App\Service\Decision\ReferenceDocumentService;
use App\Service\Decision\VersionLabelSuggester;
use App\ViewModel\Decision\MeetingReadiness;
use App\ViewModel\Decision\MeetingView;
use DateTime;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PreReRender;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use function array_map;
use function array_values;
use function assert;
use function strtoupper;
use function strval;
use function trim;

/**
 * The management page of one meeting: inline-editable agenda points with their documents, and the minutes. Edits
 * persist as they are made (blur/change), so there is no page-level form; uploads go through the XHR endpoints of
 * {@see \App\Controller\Decision\AdminMeetingController}, which trigger a re-render of this component on success.
 *
 * Like the sign-up overview this component writes to the database, so it re-asserts access on every action: a live
 * request is independent of the gated page that embedded the component.
 */
#[AsLiveComponent(
    name: 'Decision:Admin:MeetingManage',
    template: 'components/Decision/Admin/MeetingManage.html.twig',
)]
#[IsGranted(UserRoles::Board->value)]
final class MeetingManage
{
    use DefaultActionTrait;

    #[LiveProp]
    public string $type;

    #[LiveProp]
    public int $number;

    /**
     * Pending inline edits of agenda points, keyed by point id: `{number?: string, title?: string}`. Applied and
     * cleared by {@see syncEdits()} on the next request.
     *
     * @var array<int|string, array<string, string>>
     */
    #[LiveProp(writable: true)]
    public array $pointEdits = [];

    /**
     * Pending inline renames of documents, keyed by document id: `{name?: string}`.
     *
     * @var array<int|string, array<string, string>>
     */
    #[LiveProp(writable: true)]
    public array $documentEdits = [];

    /**
     * Pending version pins of reference selections, keyed by library document id.
     *
     * @var array<int|string, string>
     */
    #[LiveProp(writable: true)]
    public array $pins = [];

    /**
     * Pending time and place edits: `{startTime?: string, location?: string}`.
     *
     * @var array<string, string>
     */
    #[LiveProp(writable: true)]
    public array $details = [];

    // Transient, rendered once in this component's own markup.
    public ?string $feedback = null;
    public ?string $savedAt = null;

    private ?MeetingView $view = null;

    public function __construct(
        private readonly Security $security,
        private readonly MeetingQueryService $meetingQueryService,
        private readonly MeetingPointRepository $meetingPointRepository,
        private readonly MeetingDocumentRepository $meetingDocumentRepository,
        private readonly MeetingActivityLogRepository $meetingActivityLogRepository,
        private readonly MeetingDocumentService $meetingDocumentService,
        private readonly MeetingMinutesService $meetingMinutesService,
        private readonly ReferenceDocumentRepository $referenceDocumentRepository,
        private readonly ReferenceDocumentService $referenceDocumentService,
        private readonly MeetingLocalDetailsService $meetingLocalDetailsService,
        private readonly VersionLabelSuggester $versionLabelSuggester,
    ) {
    }

    /**
     * Every library document with this meeting's selection of it (or null when not selected), for the reference tab.
     *
     * @return list<array{document: ReferenceDocument, selection: ?MeetingReferenceSelection}>
     */
    public function getReferenceOptions(): array
    {
        $selectionsByDocumentId = [];
        foreach ($this->getView()->references as $selection) {
            $selectionsByDocumentId[(int) $selection->getReferenceDocument()->getId()] = $selection;
        }

        $options = [];
        foreach ($this->referenceDocumentRepository->findAllWithUsageCounts() as [$document]) {
            $options[] = [
                'document' => $document,
                'selection' => $selectionsByDocumentId[(int) $document->getId()] ?? null,
            ];
        }

        return $options;
    }

    public function getView(): MeetingView
    {
        $this->assertAccess();

        if (null !== $this->view) {
            return $this->view;
        }

        $view = $this->meetingQueryService->getMeetingView(
            MeetingTypes::tryFromSearch(strtoupper($this->type)),
            $this->number,
        );

        if (null === $view) {
            throw new NotFoundHttpException('Meeting not found.');
        }

        return $this->view = $view;
    }

    public function getReadiness(): MeetingReadiness
    {
        return $this->meetingQueryService->getReadiness($this->getView());
    }

    /**
     * @return list<MeetingActivityLog>
     */
    public function getActivity(): array
    {
        return $this->meetingActivityLogRepository->findRecentForMeeting($this->getView()->meeting);
    }

    public function suggestLabel(?string $previousLabel): string
    {
        return $this->versionLabelSuggester->suggest($previousLabel);
    }

    /**
     * Applies the pending inline edits delivered with this request, before rendering.
     */
    #[PreReRender]
    public function syncEdits(): void
    {
        $this->assertAccess();

        $applied = false;

        foreach ($this->pointEdits as $id => $fields) {
            $point = $this->point((int) $id);

            if (null === $point) {
                continue;
            }

            $this->meetingDocumentService->updatePoint(
                $point,
                trim(strval($fields['number'] ?? $point->getNumber())),
                trim(strval($fields['title'] ?? $point->getTitle())),
                $this->actor(),
            );
            $applied = true;
        }

        foreach ($this->documentEdits as $id => $fields) {
            $document = $this->document((int) $id);
            $name = trim(strval($fields['name'] ?? ''));

            if (
                null === $document
                || '' === $name
            ) {
                continue;
            }

            $this->meetingDocumentService->renameDocument(
                $document,
                $name,
                $this->actor(),
            );
            $applied = true;
        }

        foreach ($this->pins as $id => $versionId) {
            $document = $this->referenceDocumentRepository->find((int) $id);

            if (null === $document) {
                continue;
            }

            $version = null;
            foreach ($document->getVersions() as $candidate) {
                if ($candidate->getId() === (int) $versionId) {
                    $version = $candidate;
                    break;
                }
            }

            if (null === $version) {
                continue;
            }

            $this->referenceDocumentService->pinVersion(
                $this->meeting(),
                $document,
                $version,
                $this->actor(),
            );
            $applied = true;
        }

        if ([] !== $this->details) {
            $existing = $this->meeting()->getLocalDetails();
            $this->meetingLocalDetailsService->updateDetails(
                $this->meeting(),
                strval($this->details['startTime'] ?? $existing?->getStartTime()?->format('H:i') ?? ''),
                strval($this->details['location'] ?? $existing?->getLocation() ?? ''),
                $this->actor(),
            );
            $applied = true;
        }

        $this->pointEdits = [];
        $this->documentEdits = [];
        $this->pins = [];
        $this->details = [];

        if (!$applied) {
            return;
        }

        $this->markSaved();
    }

    #[LiveAction]
    public function toggleReference(#[LiveArg]
    int $id,): void
    {
        $this->assertAccess();

        $document = $this->referenceDocumentRepository->find($id);
        if (null === $document) {
            return;
        }

        $this->referenceDocumentService->toggleSelection(
            $this->meeting(),
            $document,
            $this->actor(),
        );
        $this->markSaved();
    }

    #[LiveAction]
    public function carryOver(): void
    {
        $this->assertAccess();

        $this->referenceDocumentService->carryOverSelection(
            $this->meeting(),
            $this->actor(),
        );
        $this->markSaved();
    }

    #[LiveAction]
    public function addPoint(): void
    {
        $this->assertAccess();

        $this->meetingDocumentService->createPoint(
            $this->getView()->meeting,
            '',
            '',
            $this->actor(),
        );
        $this->markSaved();
    }

    #[LiveAction]
    public function deletePoint(#[LiveArg]
    int $id,): void
    {
        $this->assertAccess();

        $point = $this->point($id);
        if (null === $point) {
            return;
        }

        $this->meetingDocumentService->deletePoint(
            $point,
            $this->actor(),
        );
        $this->markSaved();
    }

    #[LiveAction]
    public function deleteDocument(#[LiveArg]
    int $id,): void
    {
        $this->assertAccess();

        $document = $this->document($id);
        if (null === $document) {
            return;
        }

        $this->meetingDocumentService->deleteDocument(
            $document,
            $this->actor(),
        );
        $this->markSaved();
    }

    #[LiveAction]
    public function deleteMinutes(): void
    {
        $this->assertAccess();

        $this->meetingMinutesService->deleteMinutes(
            $this->getView()->meeting,
            $this->actor(),
        );
        $this->markSaved();
    }

    /**
     * @param array<array-key, int|string> $orderedIds
     */
    #[LiveAction]
    public function reorderPoints(#[LiveArg]
    array $orderedIds,): void
    {
        $this->assertAccess();

        $this->meetingDocumentService->reorderPoints(
            $this->getView()->meeting,
            $this->normaliseIds($orderedIds),
            $this->actor(),
        );
        $this->markSaved();
    }

    /**
     * @param array<array-key, int|string> $orderedIds
     */
    #[LiveAction]
    public function reorderDocuments(
        #[LiveArg]
        array $orderedIds,
        #[LiveArg]
        ?int $pointId = null,
    ): void {
        $this->assertAccess();

        $point = null;
        if (null !== $pointId) {
            $point = $this->point($pointId);

            if (null === $point) {
                return;
            }
        }

        $this->meetingDocumentService->reorderDocuments(
            $this->getView()->meeting,
            $point,
            $this->normaliseIds($orderedIds),
            $this->actor(),
        );
        $this->markSaved();
    }

    private function point(int $id): ?MeetingPoint
    {
        $point = $this->meetingPointRepository->find($id);

        if ($point?->getMeeting() !== $this->meeting()) {
            return null;
        }

        return $point;
    }

    private function document(int $id): ?MeetingDocument
    {
        $document = $this->meetingDocumentRepository->find($id);

        if ($document?->getMeeting() !== $this->meeting()) {
            return null;
        }

        return $document;
    }

    private function meeting(): Meeting
    {
        return $this->getView()->meeting;
    }

    private function markSaved(): void
    {
        $this->view = null;
        $this->savedAt = new DateTime()->format('H:i');
    }

    private function actor(): User
    {
        $user = $this->security->getUser();
        assert($user instanceof User);

        return $user;
    }

    private function assertAccess(): void
    {
        if (
            $this->security->isGranted(UserRoles::Board->value)
            && $this->security->isGranted(SudoVoter::ATTRIBUTE)
        ) {
            return;
        }

        throw new AccessDeniedException();
    }

    /**
     * @param array<array-key, int|string> $ids
     *
     * @return list<int>
     */
    private function normaliseIds(array $ids): array
    {
        return array_values(array_map(
            static fn (int|string $id): int => (int) $id,
            $ids,
        ));
    }
}
