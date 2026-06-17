<?php

declare(strict_types=1);

namespace App\Tests\Entity\Application;

use App\Entity\Activity\ActivityRevision;
use App\Entity\Career\Company;
use App\Entity\Decision\Member;
use App\Entity\User\CompanyUser;
use App\Entity\User\User;
use LogicException;
use PHPUnit\Framework\TestCase;

/**
 * AbstractRevision's shared workflow fields, exercised through the concrete ActivityRevision. The single-actor
 * invariant is deliberately asymmetric: a revision may be authored (or last edited) by EITHER a member or a company
 * user, or -- before either is assigned -- by neither; only being claimed by BOTH at once is forbidden. The display
 * helpers fall back member -> company -> '' for the author, and member -> company -> null for the last editor.
 */
final class AbstractRevisionInvariantsTest extends TestCase
{
    public function testNeitherActorBeingSetIsAllowed(): void
    {
        $revision = new ActivityRevision();

        // Both pairs empty: a freshly spawned revision (before authorship/editor are assigned) must not trip the guard.
        $revision->assertSingleActor();

        self::assertSame(
            '',
            $revision->getAuthorDisplayName(),
        );
        self::assertNull($revision->getLastEditorDisplayName());
    }

    public function testRejectsBeingAuthoredByBothAMemberAndACompanyUser(): void
    {
        $revision = new ActivityRevision();
        $revision->setAuthor(self::createStub(Member::class));
        $revision->setAuthorCompanyUser(self::createStub(CompanyUser::class));

        $this->expectException(LogicException::class);
        $revision->assertSingleActor();
    }

    public function testRejectsBeingLastEditedByBothAMemberAndACompanyUser(): void
    {
        $revision = new ActivityRevision();
        $revision->setLastEditedBy(self::createStub(User::class));
        $revision->setLastEditedByCompanyUser(self::createStub(CompanyUser::class));

        $this->expectException(LogicException::class);
        $revision->assertSingleActor();
    }

    public function testAuthorDisplayNamePrefersTheMemberOtherwiseTheCompanyName(): void
    {
        $member = self::createStub(Member::class);
        $member->method('getFullName')->willReturn('Jane Member');
        $byMember = new ActivityRevision();
        $byMember->setAuthor($member);
        self::assertSame(
            'Jane Member',
            $byMember->getAuthorDisplayName(),
        );

        $company = self::createStub(Company::class);
        $company->method('getName')->willReturn('ACME');
        $companyUser = self::createStub(CompanyUser::class);
        $companyUser->method('getCompany')->willReturn($company);
        $byCompany = new ActivityRevision();
        $byCompany->setAuthorCompanyUser($companyUser);
        self::assertSame(
            'ACME',
            $byCompany->getAuthorDisplayName(),
        );
    }

    public function testLastEditorDisplayNamePrefersTheMemberOtherwiseTheCompanyUser(): void
    {
        $user = self::createStub(User::class);
        $user->method('getDisplayName')->willReturn('Jane Account');
        $byMember = new ActivityRevision();
        $byMember->setLastEditedBy($user);
        self::assertSame(
            'Jane Account',
            $byMember->getLastEditorDisplayName(),
        );

        $companyUser = self::createStub(CompanyUser::class);
        $companyUser->method('getDisplayName')->willReturn('ACME login');
        $byCompany = new ActivityRevision();
        $byCompany->setLastEditedByCompanyUser($companyUser);
        self::assertSame(
            'ACME login',
            $byCompany->getLastEditorDisplayName(),
        );
    }
}
