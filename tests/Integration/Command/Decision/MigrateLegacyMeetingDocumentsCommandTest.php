<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command\Decision;

use App\Entity\Decision\Enums\MeetingTypes;
use App\Entity\Decision\LegacyMeetingDocument;
use App\Entity\Decision\LegacyMeetingMinutes;
use App\Entity\Decision\Meeting;
use App\Entity\Decision\MeetingDocument;
use App\Entity\Decision\MeetingPoint;
use App\Entity\Decision\MeetingReferenceSelection;
use App\Entity\Decision\ReferenceDocument;
use App\Tests\Integration\DatabaseTestCase;
use Override;
use Symfony\Component\Filesystem\Filesystem;

use function bin2hex;
use function dirname;
use function random_bytes;
use function sprintf;
use function sys_get_temp_dir;

/**
 * End-to-end run of the legacy document migrator against seeded meetings: version suffixes collapse into one document
 * under a freshly created agenda point, a recurring document becomes a library document with per-meeting pinned
 * selections, minutes become a versioned master, and rows whose file is gone are skipped without creating anything.
 * The seeded fixtures already contain migrated documents, which doubles as coverage for the rerun guard.
 */
final class MigrateLegacyMeetingDocumentsCommandTest extends DatabaseTestCase
{
    private string $sourceDir;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->sourceDir = sys_get_temp_dir() . '/legacy-migrator-' . bin2hex(random_bytes(8));
    }

    #[Override]
    protected function tearDown(): void
    {
        new Filesystem()->remove($this->sourceDir);

        parent::tearDown();
    }

    public function testRefusesToRunWhenMigratedDocumentsExist(): void
    {
        $result = static::runCommand('app:decision:migrate-legacy-meeting-documents');

        $this->assertCommandFailed($result);
        self::assertStringContainsString(
            '--force',
            $result->getDisplay(),
        );
    }

    public function testMigratesDocumentsReferencesAndMinutes(): void
    {
        [
            $firstMeeting, $secondMeeting
        ] = $this->oldestAlvMeetings();

        $this->legacyFile('aa/begroting-v1.pdf');
        $this->legacyFile('aa/begroting-v2.pdf');
        $this->legacyFile('aa/edl-old.pdf');
        $this->legacyFile('aa/edl-new.pdf');
        $this->legacyFile('aa/jaarplanning.pdf');
        $this->legacyFile('aa/notulen.pdf');

        $this->legacyDocument(
            $firstMeeting,
            '5.1 Begrotingswijziging (v1.0)',
            'aa/begroting-v1.pdf',
        );
        $this->legacyDocument(
            $firstMeeting,
            '5.1 Begrotingswijziging (v2.0) (03-06-2020)',
            'aa/begroting-v2.pdf',
        );
        $this->legacyDocument(
            $firstMeeting,
            'Jaarplanning commissies',
            'aa/jaarplanning.pdf',
        );
        $this->legacyDocument(
            $firstMeeting,
            'Eternal Decisionlist',
            'aa/edl-old.pdf',
        );
        $this->legacyDocument(
            $secondMeeting,
            'AV stuk 2.3 - Eternal Decisionlist',
            'aa/edl-new.pdf',
        );
        $this->legacyDocument(
            $firstMeeting,
            '9.1 Verdwenen stuk',
            'aa/missing.pdf',
        );

        $minutes = new LegacyMeetingMinutes();
        $minutes->setMeeting($firstMeeting);
        $minutes->setPath('aa/notulen.pdf');
        $this->entityManager->persist($minutes);
        $this->entityManager->flush();

        $this->assertCommandIsSuccessful(static::runCommand(
            'app:decision:migrate-legacy-meeting-documents',
            [
                '--force' => true,
                '--source-dir' => $this->sourceDir,
            ],
        ));

        // The two version-suffixed rows collapsed into one document under a new point 5.
        $document = $this->entityManager->getRepository(MeetingDocument::class)->findOneBy([
            'meeting' => $firstMeeting,
            'name' => 'Begrotingswijziging',
        ]);
        self::assertNotNull($document);
        self::assertSame(
            '5',
            $document->getPoint()?->getNumber(),
        );
        $versions = $document->getVersions()->getValues();
        self::assertCount(
            2,
            $versions,
        );
        self::assertSame(
            'v1.0',
            $versions[0]->getVersionLabel(),
        );
        self::assertSame(
            'v2.0',
            $versions[1]->getVersionLabel(),
        );
        self::assertSame(
            '2020-06-03',
            $versions[1]->getUploadedAt()?->format('Y-m-d'),
        );
        self::assertNull($versions[1]->getUploadedBy());

        // The unparseable name stayed a meeting-level document under its full original name.
        $flat = $this->entityManager->getRepository(MeetingDocument::class)->findOneBy([
            'meeting' => $firstMeeting,
            'name' => 'Jaarplanning commissies',
        ]);
        self::assertNotNull($flat);
        self::assertNull($flat->getPoint());

        // The recurring document became one library document; each meeting is pinned to the version it shipped.
        $reference = $this->entityManager->getRepository(ReferenceDocument::class)->findOneBy([
            'name' => 'Eternal Decision List',
        ]);
        self::assertNotNull($reference);
        $referenceVersions = $reference->getVersions()->getValues();
        self::assertCount(
            2,
            $referenceVersions,
        );

        $selections = $this->entityManager->getRepository(MeetingReferenceSelection::class);
        self::assertSame(
            $referenceVersions[0],
            $selections->findOneBy([
                'meeting' => $firstMeeting,
                'referenceDocument' => $reference,
            ])?->getPinnedVersion(),
        );
        self::assertSame(
            $referenceVersions[1],
            $selections->findOneBy([
                'meeting' => $secondMeeting,
                'referenceDocument' => $reference,
            ])?->getPinnedVersion(),
        );

        // The legacy minutes became a versioned master on the meeting.
        $migratedMinutes = $firstMeeting->getMinutes();
        self::assertNotNull($migratedMinutes);
        $minutesVersions = $migratedMinutes->getVersions()->getValues();
        self::assertCount(
            1,
            $minutesVersions,
        );
        self::assertSame(
            'v1.0',
            $minutesVersions[0]->getVersionLabel(),
        );

        // The row whose file is gone produced nothing, not even its agenda point.
        self::assertNull($this->entityManager->getRepository(MeetingDocument::class)->findOneBy([
            'meeting' => $firstMeeting,
            'name' => 'Verdwenen stuk',
        ]));
        self::assertNull($this->entityManager->getRepository(MeetingPoint::class)->findOneBy([
            'meeting' => $firstMeeting,
            'number' => '9',
        ]));
    }

    public function testDryRunLeavesTheDatabaseUntouched(): void
    {
        $this->legacyFile('aa/begroting-v1.pdf');
        $this->legacyDocument(
            $this->oldestAlvMeetings()[0],
            '5.1 Begrotingswijziging (v1.0)',
            'aa/begroting-v1.pdf',
        );
        $this->entityManager->flush();

        $result = static::runCommand(
            'app:decision:migrate-legacy-meeting-documents',
            [
                '--dry-run' => true,
                '--force' => true,
                '--source-dir' => $this->sourceDir,
            ],
        );

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString(
            'Dry run: nothing was written.',
            $result->getDisplay(),
        );
        self::assertNull($this->entityManager->getRepository(MeetingDocument::class)->findOneBy([
            'name' => 'Begrotingswijziging',
        ]));
    }

    /**
     * The two oldest seeded ALVs; the calendar moves with the run date, so they are resolved, not assumed.
     *
     * @return array{0: Meeting, 1: Meeting}
     */
    private function oldestAlvMeetings(): array
    {
        $meetings = $this->entityManager->getRepository(Meeting::class)->findBy(
            ['type' => MeetingTypes::ALV],
            ['number' => 'ASC'],
            2,
        );
        self::assertCount(
            2,
            $meetings,
        );

        return [
            $meetings[0],
            $meetings[1],
        ];
    }

    private function legacyDocument(
        Meeting $meeting,
        string $name,
        string $path,
    ): void {
        $document = new LegacyMeetingDocument();
        $document->setMeeting($meeting);
        $document->setName($name);
        $document->setPath($path);

        $this->entityManager->persist($document);
    }

    private function legacyFile(string $path): void
    {
        $filesystem = new Filesystem();
        $filesystem->mkdir(dirname($this->sourceDir . '/' . $path));
        $filesystem->dumpFile(
            $this->sourceDir . '/' . $path,
            sprintf(
                "%%PDF-1.4\n%% %s\n%%%%EOF\n",
                $path,
            ),
        );
    }
}
