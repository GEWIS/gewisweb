<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository\Decision;

use App\Entity\Decision\Enums\MeetingTypes;
use App\Repository\Decision\MeetingRepository;
use App\Tests\Integration\DatabaseTestCase;
use Override;

final class MeetingRepositoryTest extends DatabaseTestCase
{
    private MeetingRepository $repository;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = self::getContainer()->get(MeetingRepository::class);
    }

    public function testPaginateForOverviewCountsDecisionsAndMinutes(): void
    {
        $result = $this->repository->paginateForOverview(
            null,
            null,
            1,
            50,
        );

        self::assertNotEmpty($result['items']);
        self::assertCount(
            $result['total'],
            $result['items'],
        );

        $byMeeting = [];
        foreach ($result['items'] as [$meeting, $decisionCount, $minutesVersionCount]) {
            $byMeeting[$meeting->getType()->value . ' ' . $meeting->getNumber()] = [
                $decisionCount,
                $minutesVersionCount,
            ];
        }

        self::assertGreaterThan(
            0,
            $byMeeting['BV 0'][0],
        );
        self::assertGreaterThan(
            0,
            $byMeeting['ALV 0'][1],
        );
        self::assertSame(
            0,
            $byMeeting['ALV 1'][1],
        );
    }

    public function testPaginateForOverviewFiltersByTypeAndNumber(): void
    {
        $result = $this->repository->paginateForOverview(
            MeetingTypes::ALV,
            1,
            1,
            50,
        );

        self::assertSame(
            1,
            $result['total'],
        );
        self::assertSame(
            1,
            $result['items'][0][0]->getNumber(),
        );
    }

    public function testPaginateForOverviewClampsToThePage(): void
    {
        $result = $this->repository->paginateForOverview(
            null,
            null,
            1,
            2,
        );

        self::assertCount(
            2,
            $result['items'],
        );
        self::assertGreaterThan(
            2,
            $result['total'],
        );
    }
}
