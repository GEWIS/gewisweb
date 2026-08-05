<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository\User;

use App\Entity\User\CompanyUser;
use App\Repository\User\CompanyUserRepository;
use App\Tests\Integration\DatabaseTestCase;

final class CompanyUserRepositoryTest extends DatabaseTestCase
{
    public function testARepresentativeIsFoundByItsOwnAddressWhateverItsCasing(): void
    {
        $companyUser = $this->repository()->loadUserByIdentifier('RECRUITMENT@Nexunt.Example.Com');

        self::assertInstanceOf(
            CompanyUser::class,
            $companyUser,
        );
        self::assertSame(
            'recruitment@nexunt.example.com',
            $companyUser->getUserIdentifier(),
        );
        self::assertSame(
            'Nexunt Systems',
            $companyUser->getCompany()->getName(),
        );
    }

    public function testAnAddressNobodySignsInWithFindsNothing(): void
    {
        self::assertNull($this->repository()->loadUserByIdentifier('nobody@example.com'));
    }

    public function testACompanyCanHaveMoreThanOneRepresentative(): void
    {
        $first = $this->repository()->loadUserByIdentifier('recruitment@nexunt.example.com');
        $second = $this->repository()->loadUserByIdentifier('talent@nexunt.example.com');

        self::assertInstanceOf(
            CompanyUser::class,
            $first,
        );
        self::assertInstanceOf(
            CompanyUser::class,
            $second,
        );
        self::assertNotSame(
            $first->getId(),
            $second->getId(),
        );
        self::assertSame(
            $first->getCompany(),
            $second->getCompany(),
        );
    }

    public function testTheAdminOverviewSearchesOnTheRepresentativeAsWellAsTheCompany(): void
    {
        $byRepresentative = $this->paginate('Bram de Wit');
        self::assertCount(
            1,
            $byRepresentative,
        );
        self::assertSame(
            'talent@nexunt.example.com',
            $byRepresentative[0]->getEmail(),
        );

        // Including the representative who has moved on: the board still has to be able to find and remove them.
        self::assertCount(
            3,
            $this->paginate('Nexunt Systems'),
        );
    }

    /**
     * @return array<array-key, CompanyUser>
     */
    private function paginate(string $search): array
    {
        $paginator = $this->repository()->paginateForAdmin(
            $search,
            'name',
            'asc',
            1,
            25,
        );

        return [...$paginator];
    }

    private function repository(): CompanyUserRepository
    {
        return self::getContainer()->get(CompanyUserRepository::class);
    }
}
