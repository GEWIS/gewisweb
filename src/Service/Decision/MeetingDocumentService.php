<?php

declare(strict_types=1);

namespace App\Service\Decision;

use App\Entity\Application\Enums\StorageNamespace;
use App\Entity\Decision\Enums\MeetingActivityVerbs;
use App\Entity\Decision\Meeting;
use App\Entity\Decision\MeetingDocument;
use App\Entity\Decision\MeetingDocumentVersion;
use App\Entity\Decision\MeetingPoint;
use App\Entity\User\User;
use App\Service\Application\FileStorage;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use function array_flip;
use function count;
use function sprintf;
use function usort;

/**
 * The write side of meeting agenda management: agenda points, documents, and their versions. Every mutation is
 * recorded in the activity feed and flushed immediately, so the management page's inline edits persist one by one.
 *
 * Editing stays allowed after the meeting has taken place: renumbering points afterwards is how the board corrects a
 * shifted agenda so the synced decisions match up again.
 */
final readonly class MeetingDocumentService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FileStorage $fileStorage,
        private MeetingActivityLogger $activityLogger,
    ) {
    }

    public function createPoint(
        Meeting $meeting,
        string $number,
        string $title,
        User $actor,
    ): MeetingPoint {
        $point = new MeetingPoint();
        $point->setMeeting($meeting);
        $point->setNumber($number);
        $point->setTitle($title);
        $point->setDisplayPosition(count($meeting->getPoints()));

        $this->entityManager->persist($point);
        $this->activityLogger->log(
            $actor,
            $meeting,
            MeetingActivityVerbs::PointCreated,
            sprintf(
                '%s %s',
                $number,
                $title,
            ),
        );
        $this->entityManager->flush();

        return $point;
    }

    public function updatePoint(
        MeetingPoint $point,
        string $number,
        string $title,
        User $actor,
    ): void {
        if (
            $point->getNumber() === $number
            && $point->getTitle() === $title
        ) {
            return;
        }

        $point->setNumber($number);
        $point->setTitle($title);

        $this->activityLogger->log(
            $actor,
            $point->getMeeting(),
            MeetingActivityVerbs::PointUpdated,
            sprintf(
                '%s %s',
                $number,
                $title,
            ),
        );
        $this->entityManager->flush();
    }

    /**
     * Removing an agenda point moves its documents to the end of the meeting-level group; files are never lost.
     */
    public function deletePoint(
        MeetingPoint $point,
        User $actor,
    ): void {
        $meeting = $point->getMeeting();

        $position = 0;
        foreach ($meeting->getDocuments() as $document) {
            if (null !== $document->getPoint()) {
                continue;
            }

            $position++;
        }

        foreach ($point->getDocuments() as $document) {
            $document->setPoint(null);
            $document->setDisplayPosition($position);
            $position++;
        }

        $this->entityManager->remove($point);
        $this->activityLogger->log(
            $actor,
            $meeting,
            MeetingActivityVerbs::PointDeleted,
            sprintf(
                '%s %s',
                $point->getNumber(),
                $point->getTitle(),
            ),
        );
        $this->entityManager->flush();
    }

    /**
     * @param list<int> $orderedIds
     */
    public function reorderPoints(
        Meeting $meeting,
        array $orderedIds,
        User $actor,
    ): void {
        $positions = array_flip($orderedIds);

        foreach ($meeting->getPoints() as $point) {
            $position = $positions[(int) $point->getId()] ?? null;

            if (null === $position) {
                continue;
            }

            $point->setDisplayPosition($position);
        }

        $this->activityLogger->log(
            $actor,
            $meeting,
            MeetingActivityVerbs::PointsReordered,
            '',
        );
        $this->entityManager->flush();
    }

    public function uploadDocument(
        Meeting $meeting,
        ?MeetingPoint $point,
        string $name,
        UploadedFile $file,
        string $versionLabel,
        User $actor,
    ): MeetingDocument {
        $document = new MeetingDocument();
        $document->setMeeting($meeting);
        $document->setPoint($point);
        $document->setName($name);
        $document->setDisplayPosition(
            null === $point
                ? $this->nextMeetingLevelPosition($meeting)
                : count($point->getDocuments()),
        );

        $this->entityManager->persist($document);
        $this->entityManager->persist($this->createVersion(
            $document,
            $file,
            $versionLabel,
            $actor,
        ));

        $this->activityLogger->log(
            $actor,
            $meeting,
            MeetingActivityVerbs::DocumentUploaded,
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
        MeetingDocument $document,
        UploadedFile $file,
        string $versionLabel,
        User $actor,
    ): MeetingDocumentVersion {
        $version = $this->createVersion(
            $document,
            $file,
            $versionLabel,
            $actor,
        );

        $this->entityManager->persist($version);
        $this->activityLogger->log(
            $actor,
            $document->getMeeting(),
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
        MeetingDocument $document,
        string $name,
        User $actor,
    ): void {
        if ($document->getName() === $name) {
            return;
        }

        $document->setName($name);
        $this->activityLogger->log(
            $actor,
            $document->getMeeting(),
            MeetingActivityVerbs::DocumentRenamed,
            $name,
        );
        $this->entityManager->flush();
    }

    /**
     * Removes the document with all its versions, unlinking their stored files once the rows are gone.
     */
    public function deleteDocument(
        MeetingDocument $document,
        User $actor,
    ): void {
        $paths = [];
        foreach ($document->getVersions() as $version) {
            $paths[] = $version->getPath();
            $this->entityManager->remove($version);
        }

        $this->entityManager->remove($document);
        $this->activityLogger->log(
            $actor,
            $document->getMeeting(),
            MeetingActivityVerbs::DocumentDeleted,
            $document->getName(),
        );
        $this->entityManager->flush();

        foreach ($paths as $path) {
            $this->fileStorage->remove($path);
        }
    }

    /**
     * Reorders the documents within one agenda point or, when `$point` is null, within the meeting-level group.
     *
     * @param list<int> $orderedIds
     */
    public function reorderDocuments(
        Meeting $meeting,
        ?MeetingPoint $point,
        array $orderedIds,
        User $actor,
    ): void {
        $positions = array_flip($orderedIds);
        $documents = null === $point
            ? $meeting->getDocuments()
            : $point->getDocuments();

        foreach ($documents as $document) {
            if (
                null === $point
                && null !== $document->getPoint()
            ) {
                continue;
            }

            $position = $positions[(int) $document->getId()] ?? null;

            if (null === $position) {
                continue;
            }

            $document->setDisplayPosition($position);
        }

        $this->activityLogger->log(
            $actor,
            $meeting,
            MeetingActivityVerbs::DocumentsReordered,
            '',
        );
        $this->entityManager->flush();
    }

    private function createVersion(
        MeetingDocument $document,
        UploadedFile $file,
        string $versionLabel,
        User $actor,
    ): MeetingDocumentVersion {
        $version = new MeetingDocumentVersion();
        $version->setDocument($document);
        $version->setVersionLabel($versionLabel);
        $version->setPath($this->fileStorage->store(
            StorageNamespace::MeetingDocument,
            $file->getPathname(),
            $document->getMeeting()->getStorageScope(),
        )->path);
        $version->setUploadedBy($actor);
        $version->setUploadedAt(new DateTime());

        return $version;
    }

    private function nextMeetingLevelPosition(Meeting $meeting): int
    {
        $positions = [];
        foreach ($meeting->getDocuments() as $document) {
            if (null !== $document->getPoint()) {
                continue;
            }

            $positions[] = $document->getDisplayPosition();
        }

        if ([] === $positions) {
            return 0;
        }

        usort(
            $positions,
            static fn (int $a, int $b): int => $b <=> $a,
        );

        return $positions[0] + 1;
    }
}
