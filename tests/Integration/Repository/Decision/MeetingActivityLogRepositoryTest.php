<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository\Decision;

use App\Entity\Decision\Enums\MeetingTypes;
use App\Entity\Decision\Meeting;
use App\Entity\Decision\MeetingActivityLog;
use App\Entity\Decision\MeetingMinutes;
use App\Repository\Decision\MeetingActivityLogRepository;
use App\Tests\Integration\DatabaseTestCase;
use Override;

use function array_map;

final class MeetingActivityLogRepositoryTest extends DatabaseTestCase
{
    private MeetingActivityLogRepository $repository;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = self::getContainer()->get(MeetingActivityLogRepository::class);
    }

    public function testMeetingFeedListsNewestFirst(): void
    {
        $minutes = $this->entityManager->getRepository(MeetingMinutes::class)->findAll();
        self::assertCount(
            1,
            $minutes,
        );
        $meeting = $this->entityManager->find(
            Meeting::class,
            [
                'type' => MeetingTypes::ALV,
                'number' => $minutes[0]->getMeeting()->getNumber() + 2,
            ],
        );
        self::assertNotNull($meeting);

        $entries = $this->repository->findRecentForMeeting($meeting);

        self::assertSame(
            [
                '7b Budget explanation',
                'Budget (v2.1)',
                '7a Budget',
            ],
            array_map(
                static fn (MeetingActivityLog $entry) => $entry->getSubject(),
                $entries,
            ),
        );
    }

    public function testLibraryFeedOnlyContainsEntriesWithoutAMeeting(): void
    {
        $entries = $this->repository->findRecentForLibrary();

        self::assertNotEmpty($entries);
        foreach ($entries as $entry) {
            self::assertNull($entry->getMeeting());
        }
    }
}
