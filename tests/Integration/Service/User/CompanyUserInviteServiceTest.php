<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\User;

use App\Entity\Career\Company;
use App\Entity\Career\Enums\CompanyAuditVerbs;
use App\Message\User\CompanyUserInviteEmail;
use App\Repository\Career\CompanyAuditLogRepository;
use App\Repository\Career\CompanyRepository;
use App\Repository\User\CompanyUserInviteRepository;
use App\Repository\User\CompanyUserRepository;
use App\Service\User\CompanyUserInviteService;
use App\Tests\Integration\DatabaseTestCase;
use RuntimeException;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

final class CompanyUserInviteServiceTest extends DatabaseTestCase
{
    public function testAnInvitationIsRecordedEmailedAndShowsUpOnTheTimeline(): void
    {
        $company = $this->company();

        $invite = $this->service()->invite(
            $company,
            'new-rep@nexunt.example.com',
            'Wietske Jansen',
            null,
        );

        self::assertSame(
            'new-rep@nexunt.example.com',
            $invite->getEmail(),
        );
        self::assertFalse($invite->isExpired());
        self::assertCount(
            1,
            $this->dispatchedInviteEmails(),
        );

        $entries = self::getContainer()->get(CompanyAuditLogRepository::class)->findRecentForCompany($company);
        self::assertSame(
            CompanyAuditVerbs::RepresentativeInvited,
            $entries[0]->getVerb(),
        );
        self::assertSame(
            'new-rep@nexunt.example.com',
            $entries[0]->getDetail(),
        );
    }

    public function testAnAddressThatAlreadySignsInIsRefused(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service()->invite(
            $this->company(),
            'recruitment@nexunt.example.com',
            'Someone Else',
            null,
        );
    }

    public function testAnAddressInvitedByAnotherCompanyIsRefused(): void
    {
        $this->service()->invite(
            $this->company('orbit-analytics'),
            'shared@example.com',
            'Shared Person',
            null,
        );

        $this->expectException(RuntimeException::class);
        $this->service()->invite(
            $this->company(),
            'shared@example.com',
            'Shared Person',
            null,
        );
    }

    /**
     * Inviting the same address again reissues the one invitation rather than leaving two links in the wild.
     */
    public function testInvitingTheSameAddressTwiceReissuesTheOneInvitation(): void
    {
        $service = $this->service();
        $company = $this->company();

        $first = $service->invite(
            $company,
            'twice@nexunt.example.com',
            'Twice Invited',
            null,
        );
        $selector = $first->getSelector();

        $second = $service->invite(
            $company,
            'twice@nexunt.example.com',
            'Twice Invited',
            null,
        );

        self::assertSame(
            $first,
            $second,
        );
        self::assertNotSame(
            $selector,
            $second->getSelector(),
        );
        self::assertCount(
            2,
            $this->dispatchedInviteEmails(),
        );
    }

    public function testAcceptingCreatesTheAccountAndRetiresTheInvitation(): void
    {
        $invite = $this->service()->invite(
            $this->company(),
            'accepting@nexunt.example.com',
            'Accepting Person',
            null,
        );

        $companyUser = $this->service()->accept(
            $invite,
            'correct horse battery staple',
        );

        self::assertSame(
            'accepting@nexunt.example.com',
            $companyUser->getUserIdentifier(),
        );
        self::assertSame(
            'Nexunt Systems',
            $companyUser->getCompany()->getName(),
        );
        self::assertNotNull($companyUser->getPassword());
        self::assertNotSame(
            'correct horse battery staple',
            $companyUser->getPassword(),
        );
        self::assertNull($this->inviteRepository()->findByEmail('accepting@nexunt.example.com'));
        self::assertNotNull(
            self::getContainer()->get(CompanyUserRepository::class)
                ->loadUserByIdentifier('accepting@nexunt.example.com'),
        );
    }

    public function testRevokingLeavesNothingBehindButTheRecordThatItHappened(): void
    {
        $company = $this->company();
        $invite = $this->service()->invite(
            $company,
            'revoked@nexunt.example.com',
            'Revoked Person',
            null,
        );

        $this->service()->revoke($invite);

        self::assertNull($this->inviteRepository()->findByEmail('revoked@nexunt.example.com'));
        self::assertSame(
            CompanyAuditVerbs::InviteRevoked,
            self::getContainer()->get(CompanyAuditLogRepository::class)->findRecentForCompany($company)[0]->getVerb(),
        );
    }

    /**
     * @return list<CompanyUserInviteEmail>
     */
    private function dispatchedInviteEmails(): array
    {
        $transport = self::getContainer()->get('messenger.transport.high_priority');
        self::assertInstanceOf(
            InMemoryTransport::class,
            $transport,
        );

        $messages = [];
        foreach ($transport->getSent() as $envelope) {
            $message = $envelope->getMessage();
            if (!$message instanceof CompanyUserInviteEmail) {
                continue;
            }

            $messages[] = $message;
        }

        return $messages;
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

    private function inviteRepository(): CompanyUserInviteRepository
    {
        return self::getContainer()->get(CompanyUserInviteRepository::class);
    }

    private function service(): CompanyUserInviteService
    {
        return self::getContainer()->get(CompanyUserInviteService::class);
    }
}
