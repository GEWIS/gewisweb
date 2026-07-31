<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository\Decision;

use App\Entity\Decision\Enums\MeetingTypes;
use App\Repository\Decision\DecisionRepository;
use App\Tests\Integration\DatabaseTestCase;
use Override;

final class DecisionRepositoryTest extends DatabaseTestCase
{
    private DecisionRepository $repository;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = self::getContainer()->get(DecisionRepository::class);
    }

    /**
     * A meeting-reference query binds the meeting type; binding it with the enum class as the DBAL type used to
     * explode with "Unknown column type" once the search page actually ran this query.
     */
    public function testSearchByMeetingReferenceFindsTheMeetingsDecisions(): void
    {
        $results = $this->repository->search('BV 0');

        self::assertNotEmpty($results);
        foreach ($results as $decision) {
            self::assertSame(
                MeetingTypes::BV,
                $decision->getMeeting()->getType(),
            );
            self::assertSame(
                0,
                $decision->getMeeting()->getNumber(),
            );
        }
    }

    public function testSearchByPointReferenceNarrowsToThePoint(): void
    {
        $results = $this->repository->search('BV 0.2');

        self::assertNotEmpty($results);
        foreach ($results as $decision) {
            self::assertSame(
                2,
                $decision->getPoint(),
            );
        }
    }
}
