<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Decision;

use App\Entity\Decision\Enums\MeetingTypes;
use App\Entity\Decision\Meeting;
use App\Entity\Decision\MeetingMinutes;
use App\Repository\Decision\MeetingDocumentRepository;
use App\Service\Application\FileStorage;
use App\Service\Decision\MeetingDocumentZipBuilder;
use App\Tests\Integration\DatabaseTestCase;
use ZipArchive;

use function sort;
use function sprintf;
use function unlink;

final class MeetingDocumentZipBuilderTest extends DatabaseTestCase
{
    public function testBundlesTheLatestVersionOfEveryDocument(): void
    {
        $meeting = $this->completeGmm();

        // The test storage adapter is in-memory and empty per process; recreate the seeded documents' bytes.
        $storage = self::getContainer()->get(FileStorage::class);
        foreach (self::getContainer()->get(MeetingDocumentRepository::class)->findForMeeting($meeting) as $document) {
            foreach ($document->getVersions() as $version) {
                $storage->write(
                    $version->getPath(),
                    sprintf(
                        "%%PDF-1.4\n%% %s\n%%%%EOF\n",
                        $version->getVersionLabel(),
                    ),
                );
            }
        }

        $zipPath = $this->builder()->build($meeting);
        self::assertNotNull($zipPath);

        $zip = new ZipArchive();
        self::assertTrue($zip->open($zipPath));

        $entries = [];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entries[] = (string) $zip->getNameIndex($index);
        }

        sort($entries);
        self::assertSame(
            [
                '2. Agenda (v1.1).pdf',
                '3. Decision list (v1.0).pdf',
                '7a. Budget (v2.1).pdf',
                'Letter to the GMM (v1).pdf',
            ],
            $entries,
        );

        $zip->close();
        unlink($zipPath);
    }

    public function testMeetingWithoutDocumentsYieldsNothing(): void
    {
        $meeting = $this->entityManager->find(
            Meeting::class,
            [
                'type' => MeetingTypes::BV,
                'number' => 1800,
            ],
        );
        self::assertNotNull($meeting);

        self::assertNull($this->builder()->build($meeting));
    }

    private function builder(): MeetingDocumentZipBuilder
    {
        return self::getContainer()->get(MeetingDocumentZipBuilder::class);
    }

    private function completeGmm(): Meeting
    {
        $minutes = $this->entityManager->getRepository(MeetingMinutes::class)->findAll();
        self::assertCount(
            1,
            $minutes,
        );

        return $minutes[0]->getMeeting();
    }
}
