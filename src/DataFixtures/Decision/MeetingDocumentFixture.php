<?php

declare(strict_types=1);

namespace App\DataFixtures\Decision;

use App\DataFixtures\User\UserFixture;
use App\Entity\Application\Enums\StorageNamespace;
use App\Entity\Decision\Meeting;
use App\Entity\Decision\MeetingDocument;
use App\Entity\Decision\MeetingDocumentVersion;
use App\Entity\Decision\MeetingPoint;
use App\Entity\User\User;
use App\Service\Application\FileStorage;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Override;
use RuntimeException;

use function file_put_contents;
use function sprintf;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * Documents on the past ALVs: versioned documents under agenda points, one document that migrated from the legacy
 * flat model (no agenda point, no uploader, unknown upload date), and version history on the agenda.
 */
class MeetingDocumentFixture extends Fixture implements DependentFixtureInterface
{
    public function __construct(private readonly FileStorage $fileStorage)
    {
    }

    #[Override]
    public function load(ObjectManager $manager): void
    {
        $uploader = $this->getReference(
            'user-8000',
            User::class,
        );

        // The agenda of ALV-0 was revised once; members see v1.1.
        $agenda = $this->createDocument(
            $manager,
            'meeting-ALV-0',
            'meeting-point-ALV-0-2',
            'Agenda',
            0,
        );
        $this->createVersion(
            $manager,
            $agenda,
            'v1.0',
            $uploader,
            new DateTime('-3 weeks'),
        );
        $this->createVersion(
            $manager,
            $agenda,
            'v1.1',
            $uploader,
            new DateTime('-2 weeks'),
        );

        $decisionList = $this->createDocument(
            $manager,
            'meeting-ALV-0',
            'meeting-point-ALV-0-3',
            'Decision list',
            0,
        );
        $this->createVersion(
            $manager,
            $decisionList,
            'v1.0',
            $uploader,
            new DateTime('-2 weeks'),
        );

        // Carried over from the legacy flat model: no agenda point, no uploader, unknown upload date.
        $legacyDocument = $this->createDocument(
            $manager,
            'meeting-ALV-0',
            null,
            'Letter to the GMM',
            0,
        );
        $this->createVersion(
            $manager,
            $legacyDocument,
            'v1',
            null,
            null,
        );

        $budget = $this->createDocument(
            $manager,
            'meeting-ALV-1',
            'meeting-point-ALV-1-7a',
            'Budget',
            0,
        );
        $this->createVersion(
            $manager,
            $budget,
            'v2.1',
            $uploader,
            new DateTime('-1 week'),
        );

        $manager->flush();
    }

    /**
     * @return class-string<Fixture>[]
     */
    #[Override]
    public function getDependencies(): array
    {
        return [
            MeetingPointFixture::class,
            UserFixture::class,
        ];
    }

    private function createDocument(
        ObjectManager $manager,
        string $meetingReference,
        ?string $pointReference,
        string $name,
        int $position,
    ): MeetingDocument {
        $document = new MeetingDocument();
        $document->setMeeting($this->getReference(
            $meetingReference,
            Meeting::class,
        ));

        if (null !== $pointReference) {
            $document->setPoint($this->getReference(
                $pointReference,
                MeetingPoint::class,
            ));
        }

        $document->setName($name);
        $document->setDisplayPosition($position);

        $manager->persist($document);

        return $document;
    }

    private function createVersion(
        ObjectManager $manager,
        MeetingDocument $document,
        string $label,
        ?User $uploader,
        ?DateTime $uploadedAt,
    ): void {
        $version = new MeetingDocumentVersion();
        $version->setDocument($document);
        $version->setVersionLabel($label);
        $version->setPath($this->storePdf(
            sprintf(
                '%s %s',
                $document->getName(),
                $label,
            ),
            $document->getMeeting(),
        ));
        $version->setUploadedBy($uploader);
        $version->setUploadedAt($uploadedAt);

        $manager->persist($version);
        $manager->flush();
    }

    /**
     * Store a tiny generated PDF, so downloads work against the storage backend without shipping binaries.
     */
    private function storePdf(
        string $marker,
        Meeting $meeting,
    ): string {
        $temporaryFile = tempnam(
            sys_get_temp_dir(),
            'fixture-pdf-',
        );

        if (false === $temporaryFile) {
            throw new RuntimeException('Could not create a temporary file for a fixture PDF.');
        }

        file_put_contents(
            $temporaryFile,
            sprintf(
                "%%PDF-1.4\n%% %s\n%%%%EOF\n",
                $marker,
            ),
        );

        try {
            return $this->fileStorage->store(
                StorageNamespace::MeetingDocument,
                $temporaryFile,
                $meeting->getStorageScope(),
            )->path;
        } finally {
            unlink($temporaryFile);
        }
    }
}
