<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Career;

use App\Entity\Career\Company;
use App\Entity\Career\Enums\CompanyAuditVerbs;
use App\Entity\User\CompanyUser;
use App\Entity\User\User;
use App\Repository\Career\CompanyAuditLogRepository;
use App\Repository\Career\CompanyRepository;
use App\Repository\User\CompanyUserRepository;
use App\Service\Career\CompanyAuditLogger;
use App\Tests\Integration\DatabaseTestCase;

final class CompanyAuditLoggerTest extends DatabaseTestCase
{
    public function testAnEntryComesBackNewestFirstWithItsActorAndDetail(): void
    {
        $company = $this->company();
        $logger = $this->logger();

        $logger->log(
            $company,
            $this->boardMember(),
            CompanyAuditVerbs::RepresentativeInvited,
            'someone@example.com',
        );
        $logger->log(
            $company,
            $this->representative(),
            CompanyAuditVerbs::BannerProposed,
        );
        $this->entityManager->flush();

        $entries = $this->repository()->findRecentForCompany($company);

        self::assertCount(
            2,
            $entries,
        );
        self::assertSame(
            CompanyAuditVerbs::BannerProposed,
            $entries[0]->getVerb(),
        );
        self::assertSame(
            'Ilse Vermeer (Nexunt Systems)',
            $entries[0]->getActorDisplayName(),
        );
        self::assertSame(
            '',
            $entries[0]->getDetail(),
        );
        self::assertSame(
            CompanyAuditVerbs::RepresentativeInvited,
            $entries[1]->getVerb(),
        );
        self::assertSame(
            'someone@example.com',
            $entries[1]->getDetail(),
        );
    }

    public function testAnotherCompanysTimelineIsNotMixedIn(): void
    {
        $this->logger()->log(
            $this->company('orbit-analytics'),
            null,
            CompanyAuditVerbs::CompanyCreated,
        );
        $this->entityManager->flush();

        self::assertSame(
            [],
            $this->repository()->findRecentForCompany($this->company()),
        );
    }

    private function company(string $slug = 'nexunt'): Company
    {
        $company = self::getContainer()->get(CompanyRepository::class)->findOneBy(['slugName' => $slug]);
        self::assertInstanceOf(
            Company::class,
            $company,
        );

        return $company;
    }

    private function boardMember(): User
    {
        $user = $this->entityManager->getRepository(User::class)->find(8025);
        self::assertInstanceOf(
            User::class,
            $user,
        );

        return $user;
    }

    private function representative(): CompanyUser
    {
        $companyUser = self::getContainer()->get(CompanyUserRepository::class)
            ->loadUserByIdentifier('recruitment@nexunt.example.com');
        self::assertInstanceOf(
            CompanyUser::class,
            $companyUser,
        );

        return $companyUser;
    }

    private function logger(): CompanyAuditLogger
    {
        return new CompanyAuditLogger($this->entityManager);
    }

    private function repository(): CompanyAuditLogRepository
    {
        return self::getContainer()->get(CompanyAuditLogRepository::class);
    }
}
