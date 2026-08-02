<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository\Decision;

use App\Entity\Decision\Decision;
use App\Entity\Decision\Enums\MeetingTypes;
use App\Repository\Decision\DecisionRepository;
use App\Service\Decision\DecisionSearchQueryParser;
use App\Tests\Integration\DatabaseTestCase;
use Override;

use function count;

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
     * @return Decision[]
     */
    private function search(string $prompt): array
    {
        return $this->repository->search(new DecisionSearchQueryParser()->parse($prompt));
    }

    /**
     * A meeting-reference query binds the meeting type; binding it with the enum class as the DBAL type used to
     * explode with "Unknown column type" once the search page actually ran this query.
     */
    public function testSearchByMeetingReferenceFindsTheMeetingsDecisions(): void
    {
        $results = $this->search('BM 1800');

        self::assertNotEmpty($results);
        foreach ($results as $decision) {
            self::assertSame(
                MeetingTypes::BV,
                $decision->getMeeting()->getType(),
            );
            self::assertSame(
                1800,
                $decision->getMeeting()->getNumber(),
            );
        }
    }

    public function testSearchByPointReferenceNarrowsToThePoint(): void
    {
        $results = $this->search('BM 1800.2');

        self::assertNotEmpty($results);
        foreach ($results as $decision) {
            self::assertSame(
                2,
                $decision->getPoint(),
            );
        }
    }

    public function testExcludedTermsDropMatches(): void
    {
        // Every seeded decision contains "wordt"; the foundations also contain "opgericht".
        $baseline = $this->search('wordt');
        self::assertNotEmpty($baseline);

        $narrowed = $this->search('wordt -opgericht');
        self::assertNotEmpty($narrowed);
        self::assertLessThan(
            count($baseline),
            count($narrowed),
        );
        foreach ($narrowed as $decision) {
            self::assertStringNotContainsString(
                'opgericht',
                $decision->getContentNL(),
            );
        }
    }

    public function testQuotedPhraseMatchesAsAWhole(): void
    {
        self::assertNotEmpty($this->search('"wordt opgericht"'));
        self::assertEmpty($this->search('"opgericht wordt"'));
    }

    public function testTypeFilterRestrictsTextMatches(): void
    {
        $results = $this->search('type:bm wordt');

        self::assertNotEmpty($results);
        foreach ($results as $decision) {
            self::assertSame(
                MeetingTypes::BV,
                $decision->getMeeting()->getType(),
            );
        }

        self::assertEmpty($this->search('type:cm wordt'));
    }

    public function testAllBareWordsMustMatch(): void
    {
        self::assertEmpty($this->search('wordt xyzzynope'));
    }
}
