<?php

declare(strict_types=1);

namespace App\Tests\Entity\Application;

use App\Entity\Activity\ActivityRevision;
use App\Entity\Decision\Member;
use App\Entity\User\CompanyUser;
use App\Entity\User\User;
use PHPUnit\Framework\TestCase;

/**
 * AbstractRevision's shared workflow fields, exercised through the concrete ActivityRevision. The single-actor
 * invariant is deliberately asymmetric: a revision may be authored (or last edited) by EITHER a member or a company
 * user, or by neither (before either is assigned); only being claimed by BOTH at once is forbidden, which the setters
 * rule out by handing the revision over. The display helpers fall back member -> company -> '' for the author, and
 * member -> company -> null for the last editor.
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

    /**
     * The board picking up what a company put forward, and the other way around: authorship moves across rather than
     * accumulating, so the revision is never claimed by both.
     */
    public function testTakingOverAuthorshipReleasesTheOtherSide(): void
    {
        $revision = new ActivityRevision();
        $revision->setAuthorCompanyUser(self::createStub(CompanyUser::class));
        $revision->setAuthor(self::createStub(Member::class));

        $revision->assertSingleActor();
        self::assertNull($revision->getAuthorCompanyUser());

        $revision->setAuthorCompanyUser(self::createStub(CompanyUser::class));

        $revision->assertSingleActor();
        self::assertNull($revision->getAuthor());
    }

    public function testTakingOverAnEditReleasesTheOtherSide(): void
    {
        $revision = new ActivityRevision();
        $revision->setLastEditedByCompanyUser(self::createStub(CompanyUser::class));
        $revision->setLastEditedBy(self::createStub(User::class));

        $revision->assertSingleActor();
        self::assertNull($revision->getLastEditedByCompanyUser());

        $revision->setLastEditedByCompanyUser(self::createStub(CompanyUser::class));

        $revision->assertSingleActor();
        self::assertNull($revision->getLastEditedBy());
    }

    /**
     * Clearing one side leaves the other alone, so releasing an author does not silently reassign the revision.
     */
    public function testClearingOneActorDoesNotTouchTheOther(): void
    {
        $revision = new ActivityRevision();
        $revision->setAuthorCompanyUser(self::createStub(CompanyUser::class));
        $revision->setAuthor(null);

        self::assertNotNull($revision->getAuthorCompanyUser());
    }

    public function testAuthorDisplayNamePrefersTheMemberOtherwiseTheCompanyUser(): void
    {
        $member = self::createStub(Member::class);
        $member->method('getFullName')->willReturn('Jane Member');
        $byMember = new ActivityRevision();
        $byMember->setAuthor($member);
        self::assertSame(
            'Jane Member',
            $byMember->getAuthorDisplayName(),
        );

        $companyUser = self::createStub(CompanyUser::class);
        $companyUser->method('getDisplayName')->willReturn('Jane Rep (ACME)');
        $byCompany = new ActivityRevision();
        $byCompany->setAuthorCompanyUser($companyUser);
        self::assertSame(
            'Jane Rep (ACME)',
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
