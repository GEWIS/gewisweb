<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\User;

use App\Entity\Career\CompanyJobPackage;
use App\Entity\User\CompanyUser;
use App\Repository\User\CompanyUserRepository;
use App\Service\User\CompanyUserAccessPolicy;
use App\Tests\Integration\DatabaseTestCase;
use DateTime;
use DateTimeImmutable;

final class CompanyUserAccessPolicyTest extends DatabaseTestCase
{
    public function testARepresentativeOfACompanyUnderContractIsLetIn(): void
    {
        self::assertTrue($this->isAllowed('recruitment@nexunt.example.com'));
    }

    public function testARepresentativeOfACompanyWhoseContractLapsedIsShutOut(): void
    {
        self::assertFalse($this->isAllowed('recruitment@halcyon-mobility.example.com'));
    }

    public function testARepresentativeWhoHasMovedOnIsShutOutEvenUnderAValidContract(): void
    {
        self::assertFalse($this->isAllowed('former@nexunt.example.com'));
    }

    /**
     * A company that has signed but not started yet is preparing its profile, which is the point of letting it in
     * early.
     */
    public function testAContractThatHasNotStartedYetStillCounts(): void
    {
        $companyUser = $this->companyUser('recruitment@halcyon-mobility.example.com');

        $package = new CompanyJobPackage();
        $package->setCompany($companyUser->getCompany());
        $package->setStartingDate(new DateTime('+1 month'));
        $package->setExpirationDate(new DateTime('+1 year'));
        $package->setPublished(false);
        $this->entityManager->persist($package);
        $this->entityManager->flush();

        self::assertTrue($this->isAllowed('recruitment@halcyon-mobility.example.com'));
    }

    private function isAllowed(string $email): bool
    {
        return $this->policy()->isAllowed(
            $this->companyUser($email),
            new DateTimeImmutable('now'),
        );
    }

    private function companyUser(string $email): CompanyUser
    {
        $companyUser = self::getContainer()->get(CompanyUserRepository::class)->loadUserByIdentifier($email);
        self::assertInstanceOf(
            CompanyUser::class,
            $companyUser,
        );

        return $companyUser;
    }

    private function policy(): CompanyUserAccessPolicy
    {
        return self::getContainer()->get(CompanyUserAccessPolicy::class);
    }
}
