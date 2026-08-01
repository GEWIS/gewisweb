<?php

declare(strict_types=1);

namespace App\Twig\Components\Decision\Admin;

use App\Entity\Decision\MeetingActivityLog;
use App\Entity\Decision\ReferenceDocument;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\Decision\MeetingActivityLogRepository;
use App\Repository\Decision\ReferenceDocumentRepository;
use App\Security\User\SudoVoter;
use App\Service\Decision\ReferenceDocumentService;
use App\Service\Decision\VersionLabelSuggester;
use DateTime;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PreReRender;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use function assert;
use function strval;
use function trim;

/**
 * The association-wide reference document library: inline-renameable documents with their versions and per-document
 * usage counts. Uploads go through the XHR endpoints of
 * {@see \App\Controller\Decision\AdminReferenceDocumentController}; removing a document is blocked while any meeting
 * still selects it.
 */
#[AsLiveComponent(
    name: 'Decision:Admin:ReferenceLibrary',
    template: 'components/Decision/Admin/ReferenceLibrary.html.twig',
)]
#[IsGranted(UserRoles::Board->value)]
final class ReferenceLibrary
{
    use DefaultActionTrait;

    /**
     * Pending inline renames, keyed by document id: `{name?: string}`.
     *
     * @var array<int|string, array<string, string>>
     */
    #[LiveProp(writable: true)]
    public array $nameEdits = [];

    // Transient, rendered once in this component's own markup.
    public ?string $feedback = null;
    public ?string $savedAt = null;

    public function __construct(
        private readonly Security $security,
        private readonly ReferenceDocumentRepository $referenceDocumentRepository,
        private readonly MeetingActivityLogRepository $meetingActivityLogRepository,
        private readonly ReferenceDocumentService $referenceDocumentService,
        private readonly VersionLabelSuggester $versionLabelSuggester,
    ) {
    }

    /**
     * @return list<array{0: ReferenceDocument, 1: int}>
     */
    public function getDocuments(): array
    {
        $this->assertAccess();

        return $this->referenceDocumentRepository->findAllWithUsageCounts();
    }

    /**
     * @return list<MeetingActivityLog>
     */
    public function getActivity(): array
    {
        return $this->meetingActivityLogRepository->findRecentForLibrary();
    }

    public function suggestLabel(?string $previousLabel): string
    {
        return $this->versionLabelSuggester->suggest($previousLabel);
    }

    #[PreReRender]
    public function syncEdits(): void
    {
        $this->assertAccess();

        $applied = false;

        foreach ($this->nameEdits as $id => $fields) {
            $document = $this->referenceDocumentRepository->find((int) $id);
            $name = trim(strval($fields['name'] ?? ''));

            if (
                null === $document
                || '' === $name
            ) {
                continue;
            }

            $this->referenceDocumentService->renameDocument(
                $document,
                $name,
                $this->actor(),
            );
            $applied = true;
        }

        $this->nameEdits = [];

        if (!$applied) {
            return;
        }

        $this->savedAt = new DateTime()->format('H:i');
    }

    #[LiveAction]
    public function deleteDocument(#[LiveArg]
    int $id,): void
    {
        $this->assertAccess();

        $document = $this->referenceDocumentRepository->find($id);
        if (null === $document) {
            return;
        }

        try {
            $this->referenceDocumentService->deleteDocument(
                $document,
                $this->actor(),
            );
        } catch (RuntimeException $exception) {
            $this->feedback = $exception->getMessage();
        }
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
}
