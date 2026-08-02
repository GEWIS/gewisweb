<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository\Decision;

use App\Entity\Decision\ReferenceDocument;
use App\Repository\Decision\ReferenceDocumentRepository;
use App\Tests\Integration\DatabaseTestCase;
use Override;

use function array_map;

final class ReferenceDocumentRepositoryTest extends DatabaseTestCase
{
    private ReferenceDocumentRepository $repository;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = self::getContainer()->get(ReferenceDocumentRepository::class);
    }

    public function testUsageCountsAreOrderedByNameAndCountSelections(): void
    {
        $rows = $this->repository->findAllWithUsageCounts();

        self::assertSame(
            [
                'Financial Definition List',
                'Scenarios and Procedures',
            ],
            array_map(
                static fn (array $row) => $row[0]->getName(),
                $rows,
            ),
        );
        self::assertSame(
            [
                1,
                3,
            ],
            array_map(
                static fn (array $row) => $row[1],
                $rows,
            ),
        );
    }

    public function testCountUsageMatchesTheSelectionsOfOneDocument(): void
    {
        $scenarios = $this->entityManager->getRepository(ReferenceDocument::class)
            ->findOneBy(['name' => 'Scenarios and Procedures']);
        self::assertNotNull($scenarios);

        self::assertSame(
            3,
            $this->repository->countUsage($scenarios),
        );
    }
}
