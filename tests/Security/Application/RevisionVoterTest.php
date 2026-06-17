<?php

declare(strict_types=1);

namespace App\Tests\Security\Application;

use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Application\RevisableInterface;
use App\Entity\Application\RevisionInterface;
use App\Entity\Career\Company;
use App\Entity\Decision\Member;
use App\Entity\Decision\Organ;
use App\Entity\Decision\OrganMember;
use App\Entity\User\CompanyUser;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Security\Application\RevisionVoter;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * The single authorization point for every revision chain (activities, companies, vacancies), consumed both by
 * `#[IsGranted(...)]` and by the workflow guards. Its rules are security-critical, so this pins the full matrix:
 * reviewing is board-wide but C4 (CompanyAdmin) may approve only the careers domains and never activities; ownership
 * is granted by being the creator, the revision's author, the organising organ's member, or (for company users) the
 * owning company; editing in place is allowed only while a revision is still a Draft; and anonymous users are denied.
 *
 * The subject is stubbed at the {@see RevisableInterface}/{@see RevisionInterface} seam so each rule is exercised in
 * isolation, without standing up a full entity graph.
 */
final class RevisionVoterTest extends TestCase
{
    public function testAbstainsForAnUnsupportedAttribute(): void
    {
        $voter = new RevisionVoter($this->security());

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $this->tokenFor($this->userFor($this->member(1))),
                $this->revisable(),
                ['SOME_OTHER_ATTRIBUTE'],
            ),
        );
    }

    public function testAbstainsForAnUnsupportedSubject(): void
    {
        $voter = new RevisionVoter($this->security());

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $this->tokenFor($this->userFor($this->member(1))),
                new stdClass(),
                [RevisionVoter::VIEW],
            ),
        );
    }

    public function testAnonymousUsersAreDenied(): void
    {
        $voter = new RevisionVoter($this->security());

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                new NullToken(),
                $this->revisable(),
                [RevisionVoter::VIEW],
            ),
        );
    }

    public function testBoardMembersMayApproveAnyDomain(): void
    {
        $voter = new RevisionVoter($this->security(board: true));
        $token = $this->tokenFor($this->userFor($this->member(1)));

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote(
                $token,
                $this->revisable('activity'),
                [RevisionVoter::APPROVE],
            ),
        );
    }

    public function testCompanyAdminsMayApproveCareerDomains(): void
    {
        $voter = new RevisionVoter($this->security(companyAdmin: true));
        $token = $this->tokenFor($this->userFor($this->member(1)));

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote(
                $token,
                $this->revisable('vacancy'),
                [RevisionVoter::APPROVE],
            ),
        );
    }

    public function testCompanyAdminsMayNotApproveActivities(): void
    {
        $voter = new RevisionVoter($this->security(companyAdmin: true));
        $token = $this->tokenFor($this->userFor($this->member(1)));

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                $this->revisable('activity'),
                [RevisionVoter::APPROVE],
            ),
        );
    }

    public function testTheCreatorOwnsTheResourceAndMaySubmit(): void
    {
        $voter = new RevisionVoter($this->security());
        $token = $this->tokenFor($this->userFor($this->member(100)));

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote(
                $token,
                $this->revisable(creator: $this->member(100)),
                [RevisionVoter::SUBMIT],
            ),
        );
    }

    public function testTheRevisionAuthorOwnsThatRevision(): void
    {
        $voter = new RevisionVoter($this->security());
        $token = $this->tokenFor($this->userFor($this->member(200)));

        // Creator is someone else and there is no organ tie; ownership comes solely from having authored the revision.
        $revisable = $this->revisable(creator: $this->member(999));
        $revision = $this->revision(
            $revisable,
            author: $this->member(200),
        );

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote(
                $token,
                $revision,
                [RevisionVoter::SUBMIT],
            ),
        );
    }

    public function testAnInstalledOrganMemberOwnsTheResource(): void
    {
        $voter = new RevisionVoter($this->security());
        $organ = $this->organ(42);
        $member = $this->member(
            5,
            [$this->organMemberOf($this->organ(42))],
        );

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote(
                $this->tokenFor($this->userFor($member)),
                $this->revisable(organ: $organ),
                [RevisionVoter::VIEW],
            ),
        );
    }

    public function testAMemberOfADifferentOrganIsNotAnOwner(): void
    {
        $voter = new RevisionVoter($this->security());
        $member = $this->member(
            5,
            [$this->organMemberOf($this->organ(7))],
        );

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $this->tokenFor($this->userFor($member)),
                $this->revisable(organ: $this->organ(42)),
                [RevisionVoter::SUBMIT],
            ),
        );
    }

    public function testACompanyUserOwnsOnlyItsOwnCompanysResources(): void
    {
        $voter = new RevisionVoter($this->security());

        $ownToken = $this->tokenFor($this->companyUserOf($this->companyEntity(5)));
        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote(
                $ownToken,
                $this->revisable(
                    'company',
                    company: $this->companyEntity(5),
                ),
                [RevisionVoter::VIEW],
            ),
        );

        $otherToken = $this->tokenFor($this->companyUserOf($this->companyEntity(6)));
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $otherToken,
                $this->revisable(
                    'company',
                    company: $this->companyEntity(5),
                ),
                [RevisionVoter::VIEW],
            ),
        );
    }

    public function testEditingIsAllowedOnlyWhileTheRevisionIsADraft(): void
    {
        $voter = new RevisionVoter($this->security());
        $token = $this->tokenFor($this->userFor($this->member(100)));

        $draftSubject = $this->revisable(
            creator: $this->member(100),
            currentRevision: $this->revision(
                $this->revisable(),
                RevisionStatus::Draft,
            ),
        );
        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote(
                $token,
                $draftSubject,
                [RevisionVoter::EDIT],
            ),
        );

        $submittedSubject = $this->revisable(
            creator: $this->member(100),
            currentRevision: $this->revision(
                $this->revisable(),
                RevisionStatus::Submitted,
            ),
        );
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                $submittedSubject,
                [RevisionVoter::EDIT],
            ),
        );
    }

    private function security(
        bool $board = false,
        bool $companyAdmin = false,
    ): Security {
        $security = self::createStub(Security::class);
        $security->method('isGranted')->willReturnCallback(
            static fn (string $role): bool => match ($role) {
                UserRoles::Board->value => $board,
                UserRoles::CompanyAdmin->value => $companyAdmin,
                default => false,
            },
        );

        return $security;
    }

    /**
     * @param OrganMember[] $organInstallations
     */
    private function member(
        int $lidnr,
        array $organInstallations = [],
    ): Member {
        $member = self::createStub(Member::class);
        $member->method('getLidnr')->willReturn($lidnr);
        $member->method('getCurrentOrganInstallations')->willReturn(new ArrayCollection($organInstallations));

        return $member;
    }

    private function userFor(Member $member): User
    {
        $user = self::createStub(User::class);
        $user->method('getMember')->willReturn($member);

        return $user;
    }

    private function companyUserOf(Company $company): CompanyUser
    {
        $companyUser = self::createStub(CompanyUser::class);
        $companyUser->method('getCompany')->willReturn($company);

        return $companyUser;
    }

    private function organMemberOf(Organ $organ): OrganMember
    {
        $organMember = self::createStub(OrganMember::class);
        $organMember->method('getOrgan')->willReturn($organ);

        return $organMember;
    }

    private function organ(int $id): Organ
    {
        $organ = self::createStub(Organ::class);
        $organ->method('getId')->willReturn($id);

        return $organ;
    }

    private function companyEntity(int $id): Company
    {
        $company = self::createStub(Company::class);
        $company->method('getId')->willReturn($id);

        return $company;
    }

    private function tokenFor(?object $user): TokenInterface
    {
        $token = self::createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    private function revisable(
        string $resourceId = 'activity',
        ?Member $creator = null,
        ?Organ $organ = null,
        ?Company $company = null,
        ?RevisionInterface $currentRevision = null,
    ): RevisableInterface {
        $revisable = self::createStub(RevisableInterface::class);
        $revisable->method('getResourceId')->willReturn($resourceId);
        $revisable->method('getResourceCreator')->willReturn($creator);
        $revisable->method('getResourceOrgan')->willReturn($organ);
        $revisable->method('getResourceCompany')->willReturn($company);
        $revisable->method('getCurrentRevision')->willReturn($currentRevision);

        return $revisable;
    }

    private function revision(
        RevisableInterface $revisable,
        RevisionStatus $status = RevisionStatus::Draft,
        ?Member $author = null,
    ): RevisionInterface {
        $revision = self::createStub(RevisionInterface::class);
        $revision->method('getRevisable')->willReturn($revisable);
        $revision->method('getStatus')->willReturn($status);
        $revision->method('getAuthor')->willReturn($author);

        return $revision;
    }
}
