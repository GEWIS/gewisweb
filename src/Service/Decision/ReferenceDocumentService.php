<?php

declare(strict_types=1);

namespace App\Service\Decision;

use App\Entity\Application\Enums\StorageNamespace;
use App\Entity\Decision\Enums\MeetingActivityVerbs;
use App\Entity\Decision\Meeting;
use App\Entity\Decision\MeetingReferenceSelection;
use App\Entity\Decision\ReferenceDocument;
use App\Entity\Decision\ReferenceDocumentVersion;
use App\Entity\User\User;
use App\Repository\Decision\MeetingReferenceSelectionRepository;
use App\Repository\Decision\MeetingRepository;
use App\Repository\Decision\ReferenceDocumentRepository;
use App\Service\Application\FileStorage;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\Translation\TranslatorInterface;

use function sprintf;

/**
 * The write side of the association-wide reference document library and each meeting's selection from it. A selection
 * always pins the exact version members see: uploading a new library version never changes an existing selection, the
 * board repins on purpose.
 */
final readonly class ReferenceDocumentService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FileStorage $fileStorage,
        private MeetingActivityLogger $activityLogger,
        private MeetingRepository $meetingRepository,
        private ReferenceDocumentRepository $referenceDocumentRepository,
        private MeetingReferenceSelectionRepository $selectionRepository,
        private TranslatorInterface $translator,
    ) {
    }

    public function createDocument(
        string $name,
        UploadedFile $file,
        string $versionLabel,
        User $actor,
    ): ReferenceDocument {
        $document = new ReferenceDocument();
        $document->setName($name);

        $this->entityManager->persist($document);
        $this->entityManager->persist($this->createVersion(
            $document,
            $file,
            $versionLabel,
            $actor,
        ));

        $this->activityLogger->log(
            $actor,
            null,
            MeetingActivityVerbs::ReferenceDocumentCreated,
            sprintf(
                '%s (%s)',
                $name,
                $versionLabel,
            ),
        );
        $this->entityManager->flush();

        return $document;
    }

    public function uploadVersion(
        ReferenceDocument $document,
        UploadedFile $file,
        string $versionLabel,
        User $actor,
    ): ReferenceDocumentVersion {
        $version = $this->createVersion(
            $document,
            $file,
            $versionLabel,
            $actor,
        );

        $this->entityManager->persist($version);
        $this->activityLogger->log(
            $actor,
            null,
            MeetingActivityVerbs::DocumentVersionUploaded,
            sprintf(
                '%s (%s)',
                $document->getName(),
                $versionLabel,
            ),
        );
        $this->entityManager->flush();

        return $version;
    }

    public function renameDocument(
        ReferenceDocument $document,
        string $name,
        User $actor,
    ): void {
        if ($document->getName() === $name) {
            return;
        }

        $document->setName($name);
        $this->activityLogger->log(
            $actor,
            null,
            MeetingActivityVerbs::ReferenceDocumentRenamed,
            $name,
        );
        $this->entityManager->flush();
    }

    /**
     * Removing a library document is blocked while any meeting still selects it, so history stays accurate.
     *
     * @throws RuntimeException when the document is still in use.
     */
    public function deleteDocument(
        ReferenceDocument $document,
        User $actor,
    ): void {
        $usage = $this->referenceDocumentRepository->countUsage($document);

        if ($usage > 0) {
            throw new RuntimeException($this->translator->trans(
                'This document is used by %count% meetings; remove it from their selections first.',
                ['%count%' => $usage],
            ));
        }

        $paths = [];
        foreach ($document->getVersions() as $version) {
            $paths[] = $version->getPath();
            $this->entityManager->remove($version);
        }

        $this->entityManager->remove($document);
        $this->activityLogger->log(
            $actor,
            null,
            MeetingActivityVerbs::ReferenceDocumentDeleted,
            $document->getName(),
        );
        $this->entityManager->flush();

        foreach ($paths as $path) {
            $this->fileStorage->remove($path);
        }
    }

    /**
     * Selects or deselects a library document for a meeting; a fresh selection pins the current latest version.
     */
    public function toggleSelection(
        Meeting $meeting,
        ReferenceDocument $document,
        User $actor,
    ): void {
        $selection = $this->selectionRepository->findOneBy([
            'meeting' => $meeting,
            'referenceDocument' => $document,
        ]);

        if (null === $selection) {
            $latest = $document->getLatestVersion();
            if (null === $latest) {
                return;
            }

            $selection = new MeetingReferenceSelection();
            $selection->setMeeting($meeting);
            $selection->setReferenceDocument($document);
            $selection->setPinnedVersion($latest);

            $this->entityManager->persist($selection);
            $this->activityLogger->log(
                $actor,
                $meeting,
                MeetingActivityVerbs::ReferenceSelected,
                $document->getName(),
            );
        } else {
            $this->entityManager->remove($selection);
            $this->activityLogger->log(
                $actor,
                $meeting,
                MeetingActivityVerbs::ReferenceDeselected,
                $document->getName(),
            );
        }

        $this->entityManager->flush();
    }

    /**
     * Pins a meeting's selection to one version of the document.
     */
    public function pinVersion(
        Meeting $meeting,
        ReferenceDocument $document,
        ReferenceDocumentVersion $version,
        User $actor,
    ): void {
        $selection = $this->selectionRepository->findOneBy([
            'meeting' => $meeting,
            'referenceDocument' => $document,
        ]);

        if (
            null === $selection
            || $version->getReferenceDocument() !== $document
        ) {
            return;
        }

        $selection->setPinnedVersion($version);
        $this->activityLogger->log(
            $actor,
            $meeting,
            MeetingActivityVerbs::ReferencePinned,
            sprintf(
                '%s (%s)',
                $document->getName(),
                $version->getVersionLabel(),
            ),
        );
        $this->entityManager->flush();
    }

    /**
     * Copies the selection (including pins) from the previous meeting of the same type; existing selections of the
     * target meeting are kept as they are. Returns how many selections were copied.
     */
    public function carryOverSelection(
        Meeting $meeting,
        User $actor,
    ): int {
        $previous = $this->meetingRepository->findPrevious(
            $meeting,
            1,
        );

        if ([] === $previous) {
            return 0;
        }

        $existing = [];
        foreach ($this->selectionRepository->findForMeeting($meeting) as $selection) {
            $existing[(int) $selection->getReferenceDocument()->getId()] = true;
        }

        $copied = 0;
        foreach ($this->selectionRepository->findForMeeting($previous[0]) as $selection) {
            if (isset($existing[(int) $selection->getReferenceDocument()->getId()])) {
                continue;
            }

            $copy = new MeetingReferenceSelection();
            $copy->setMeeting($meeting);
            $copy->setReferenceDocument($selection->getReferenceDocument());
            $copy->setPinnedVersion($selection->getPinnedVersion());

            $this->entityManager->persist($copy);
            $copied++;
        }

        if (0 === $copied) {
            return 0;
        }

        $this->activityLogger->log(
            $actor,
            $meeting,
            MeetingActivityVerbs::ReferenceCarriedOver,
            sprintf(
                '%s %d',
                $previous[0]->getType()->value,
                $previous[0]->getNumber(),
            ),
        );
        $this->entityManager->flush();

        return $copied;
    }

    private function createVersion(
        ReferenceDocument $document,
        UploadedFile $file,
        string $versionLabel,
        User $actor,
    ): ReferenceDocumentVersion {
        $version = new ReferenceDocumentVersion();
        $version->setReferenceDocument($document);
        $version->setVersionLabel($versionLabel);
        $version->setPath($this->fileStorage->store(
            StorageNamespace::ReferenceDocument,
            $file->getPathname(),
        )->path);
        $version->setUploadedBy($actor);
        $version->setUploadedAt(new DateTime());

        return $version;
    }
}
