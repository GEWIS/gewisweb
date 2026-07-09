<?php

declare(strict_types=1);

namespace App\Security\Application;

use App\Entity\Application\RevisableInterface;
use App\Entity\Application\RevisionInterface;
use App\Entity\Decision\Member;
use App\Entity\Decision\Organ;
use App\Entity\User\CompanyUser;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

use function in_array;

/**
 * Authorizes actions on a revision chain, for any {@see RevisableInterface} domain (activities, jobs, companies).
 *
 * The subject may be either a {@see RevisionInterface} (a specific revision) or a {@see RevisableInterface} (the
 * aggregate, whose current revision is used). Consumed directly via `#[IsGranted(...)]` and by the workflow guard
 * listeners ({@see \App\EventListener\Application\RevisionGuardListener}).
 *
 * @extends Voter<string, RevisionInterface|RevisableInterface>
 */
final class RevisionVoter extends Voter
{
    public const string VIEW = 'REVISION_VIEW';
    public const string EDIT = 'REVISION_EDIT';
    public const string SUBMIT = 'REVISION_SUBMIT';
    public const string APPROVE = 'REVISION_APPROVE';
    public const string REOPEN = 'REVISION_REOPEN';
    public const string COMMENT = 'REVISION_COMMENT';

    private const array ATTRIBUTES = [
        self::VIEW,
        self::EDIT,
        self::SUBMIT,
        self::APPROVE,
        self::REOPEN,
        self::COMMENT,
    ];

    public function __construct(
        private readonly Security $security,
    ) {
    }

    #[Override]
    protected function supports(
        string $attribute,
        mixed $subject,
    ): bool {
        if (
            !in_array(
                $attribute,
                self::ATTRIBUTES,
                true,
            )
        ) {
            return false;
        }

        return $subject instanceof RevisionInterface
            || $subject instanceof RevisableInterface;
    }

    #[Override]
    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        if ($token instanceof NullToken) {
            return false;
        }

        $user = $token->getUser();
        $revisable = $this->resolveRevisable($subject);
        $revision = $this->resolveRevision($subject);

        // Reviewing (approve/reject/...) is members-only and naturally false for company users (they lack the roles).
        $canApprove = $this->canApprove($revisable);
        $isOwner = $this->isOwner(
            $user,
            $revisable,
            $revision,
        );

        return match ($attribute) {
            // Owners (creator, revision author, organ member, or the owning company's users) and reviewers may look.
            self::VIEW => $canApprove || $isOwner,
            // Approving, rejecting, requesting changes, starting a review and closing are reviewer-only.
            self::APPROVE => $canApprove,
            // Only a Draft is editable in place; anything submitted/approved/etc. is immutable and must be revised
            // through a freshly spawned revision instead.
            self::EDIT => $this->isEditable($revision) && ($isOwner || $canApprove),
            self::SUBMIT => $isOwner || $canApprove,
            self::REOPEN => $isOwner || $canApprove,
            self::COMMENT => $canApprove || $isOwner,
            default => false,
        };
    }

    /**
     * Who may review (approve/reject/request changes/start review/close) a chain. The board may review everything;
     * the careers portal (jobs & companies) is additionally reviewed by C4, the corporate contact committee, whose
     * members hold {@see UserRoles::CompanyAdmin}.
     */
    private function canApprove(RevisableInterface $revisable): bool
    {
        if ($this->security->isGranted(UserRoles::Board->value)) {
            return true;
        }

        return in_array(
            $revisable->getResourceId(),
            [
                'vacancy',
                'company',
            ],
            true,
        )
            && $this->security->isGranted(UserRoles::CompanyAdmin->value);
    }

    /**
     * Whether the current user owns the resource. A member owns it when they created it, authored the revision under
     * consideration, or are installed in its organising organ. A company user owns it when it belongs to their company
     * (company-scoped editing of vacancies/profiles).
     */
    private function isOwner(
        ?object $user,
        RevisableInterface $revisable,
        ?RevisionInterface $revision,
    ): bool {
        if ($user instanceof User) {
            $member = $user->getMember();

            if ($revisable->getResourceCreator()?->getLidnr() === $member->getLidnr()) {
                return true;
            }

            if (
                null !== $revision
                && $revision->getAuthor()?->getLidnr() === $member->getLidnr()
            ) {
                return true;
            }

            return $this->isOrganMember(
                $member,
                $revisable->getResourceOrgan(),
            );
        }

        if ($user instanceof CompanyUser) {
            $company = $revisable->getResourceCompany();

            return null !== $company
                && null !== $company->getId()
                && $company->getId() === $user->getCompany()->getId();
        }

        return false;
    }

    private function isOrganMember(
        Member $member,
        ?Organ $organ,
    ): bool {
        if (null === $organ) {
            return false;
        }

        foreach ($member->getCurrentOrganInstallations() as $installation) {
            if ($installation->getOrgan()->getId() === $organ->getId()) {
                return true;
            }
        }

        return false;
    }

    private function isEditable(?RevisionInterface $revision): bool
    {
        return null !== $revision
            && $revision->getStatus()->isEditableByAuthor();
    }

    private function resolveRevisable(RevisionInterface|RevisableInterface $subject): RevisableInterface
    {
        return $subject instanceof RevisionInterface
            ? $subject->getRevisable()
            : $subject;
    }

    private function resolveRevision(RevisionInterface|RevisableInterface $subject): ?RevisionInterface
    {
        return $subject instanceof RevisionInterface
            ? $subject
            : $subject->getCurrentRevision();
    }
}
