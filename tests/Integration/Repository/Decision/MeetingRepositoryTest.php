<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository\Decision;

use App\Entity\Decision\Enums\MeetingTypes;
use App\Entity\Decision\MeetingMinutes;
use App\Repository\Decision\MeetingRepository;
use App\Tests\Integration\DatabaseTestCase;
use Override;

use function count;

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
            100,
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

        // The oldest BM founded two committees; the complete GMM has its revised minutes, the one after has none.
        $completeNumber = $this->completeGmmNumber();
        self::assertGreaterThanOrEqual(
            2,
            $byMeeting['BV 1800'][0],
        );
        self::assertSame(
            2,
            $byMeeting['ALV ' . $completeNumber][1],
        );
        self::assertSame(
            0,
            $byMeeting['ALV ' . ($completeNumber + 1)][1],
        );
    }

    public function testPaginateForOverviewFiltersByTypeAndNumber(): void
    {
        $completeNumber = $this->completeGmmNumber();
        $result = $this->repository->paginateForOverview(
            MeetingTypes::ALV,
            $completeNumber,
            1,
            50,
        );

        self::assertSame(
            1,
            $result['total'],
        );
        self::assertSame(
            $completeNumber,
            $result['items'][0][0]->getNumber(),
        );
    }

    public function testFindUpcomingALVsListsSoonestFirst(): void
    {
        $previousDate = null;
        $meetings = $this->repository->findUpcomingALVs();

        self::assertGreaterThanOrEqual(
            2,
            count($meetings),
        );
        foreach ($meetings as $meeting) {
            self::assertSame(
                MeetingTypes::ALV,
                $meeting->getType(),
            );

            if (null !== $previousDate) {
                self::assertGreaterThan(
                    $previousDate,
                    $meeting->getDate(),
                );
            }

            $previousDate = $meeting->getDate();
        }
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

    private function completeGmmNumber(): int
    {
        $minutes = $this->entityManager->getRepository(MeetingMinutes::class)->findAll();
        self::assertCount(
            1,
            $minutes,
        );

        return $minutes[0]->getMeeting()->getNumber();
    }
}
