<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository\Decision;

use App\Entity\Decision\Member;
use App\Repository\Decision\MemberRepository;
use App\Tests\Integration\DatabaseTestCase;
use Override;

use function array_column;
use function mb_strtolower;
use function mb_substr;

final class MemberDirectorySearchTest extends DatabaseTestCase
{
    private MemberRepository $repository;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = self::getContainer()->get(MemberRepository::class);
    }

    public function testSearchDirectoryFindsACurrentMemberByNameFragment(): void
    {
        $member = $this->entityManager->find(
            Member::class,
            8000,
        );
        self::assertNotNull($member);

        $results = $this->repository->searchDirectory(mb_strtolower(mb_substr(
            $member->getLastName(),
            0,
            4,
        )));

        self::assertNotEmpty($results);
        self::assertContains(
            8000,
            array_column(
                $results,
                'lidnr',
            ),
        );
    }

    public function testSearchDirectoryCapsItsResults(): void
    {
        // A single character matches broadly across the seeded Faker names.
        $results = $this->repository->searchDirectory(
            'a',
            5,
        );

        self::assertCount(
            5,
            $results,
        );
    }
}
