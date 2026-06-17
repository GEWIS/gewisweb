<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Base for integration tests that need a real Doctrine flush against a real database.
 *
 * Tests run against a MariaDB matching production (dev and CI both run one), isolated onto the `_test`
 * database that the `when@test` doctrine config targets. The schema and the full {@see \App\DataFixtures}
 * dataset are loaded once out of band (`make test-prepare`, or the CI prepare steps). `dama/doctrine-test-bundle`
 * wraps each test in a transaction that is rolled back afterwards, so every test sees the same seeded data yet its own
 * writes never leak. Reference the seeded entities by querying for them.
 *
 * Use this only for behaviour that is genuinely emergent from the UnitOfWork or real queries; pure domain logic stays a
 * plain unit test.
 */
abstract class DatabaseTestCase extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;

    #[Override]
    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }
}
