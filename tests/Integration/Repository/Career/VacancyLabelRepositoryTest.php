<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository\Career;

use App\Repository\Career\VacancyLabelRepository;
use App\Tests\Integration\DatabaseTestCase;

use function count;

final class VacancyLabelRepositoryTest extends DatabaseTestCase
{
    /**
     * The overview only shows how often a label is used, and whether that is zero decides whether it may still be
     * removed, so the count is asked of the database rather than read off a hydrated collection.
     */
    public function testALabelIsListedWithHowManyRevisionsCarryIt(): void
    {
        $repository = self::getContainer()->get(VacancyLabelRepository::class);

        $rows = $repository->findAllWithUsage();

        self::assertCount(
            count($repository->findAll()),
            $rows,
        );

        $counted = 0;
        foreach ($rows as $row) {
            self::assertSame(
                count($row['label']->getRevisions()),
                $row['usage'],
            );
            $counted += $row['usage'];
        }

        // The seed puts labels on vacancies, so a run where nothing is counted means the join silently dropped them.
        self::assertGreaterThan(
            0,
            $counted,
        );
    }
}
