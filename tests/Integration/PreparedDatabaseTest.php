<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Decision\Member;

/**
 * A canary that the test database was prepared (schema + fixtures) before the suite ran. If this fails, every other
 * integration test would fail obscurely; here the failure points straight at the missing `make test-prepare` step.
 */
final class PreparedDatabaseTest extends DatabaseTestCase
{
    public function testTheFixtureDatasetIsLoaded(): void
    {
        $members = $this->entityManager->getRepository(Member::class)->count([]);

        self::assertGreaterThan(
            0,
            $members,
            'The test database is not seeded; run `make test-prepare` (or the CI prepare steps).',
        );
    }
}
