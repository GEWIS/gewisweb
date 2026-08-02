<?php

declare(strict_types=1);

namespace App\Tests\Entity\Career;

use App\Entity\Career\CompanyAuditLog;
use App\Entity\User\CompanyUser;
use App\Entity\User\User;
use LogicException;
use PHPUnit\Framework\TestCase;

final class CompanyAuditLogTest extends TestCase
{
    public function testAnEntryNobodyIsBehindIsAllowed(): void
    {
        $entry = new CompanyAuditLog();
        $entry->assertSingleActor();

        self::assertNull($entry->getActorDisplayName());
    }

    public function testRejectsBeingAttributedToBothAMemberAndARepresentative(): void
    {
        $entry = new CompanyAuditLog();
        $entry->setActor(self::createStub(User::class));
        $entry->setActorCompanyUser(self::createStub(CompanyUser::class));

        $this->expectException(LogicException::class);
        $entry->assertSingleActor();
    }

    public function testTheActorNameComesFromWhicheverSideActed(): void
    {
        $companyUser = self::createStub(CompanyUser::class);
        $companyUser->method('getDisplayName')->willReturn('Ilse Vermeer (Nexunt Systems)');

        $entry = new CompanyAuditLog();
        $entry->setActorCompanyUser($companyUser);

        self::assertSame(
            'Ilse Vermeer (Nexunt Systems)',
            $entry->getActorDisplayName(),
        );
    }
}
