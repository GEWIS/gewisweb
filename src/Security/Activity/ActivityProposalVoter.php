<?php

declare(strict_types=1);

namespace App\Security\Activity;

use App\Entity\Activity\ActivityProposal;
use App\Entity\Decision\Member;
use App\Entity\Decision\Organ;
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
 * Authorises what can be done to an activity proposal in the option calendar.
 *
 * A proposal belongs to the body hosting the activity, so whoever is installed in that body may look at it, change it
 * while it is still waiting, and take it back. Deciding which of its dates is reserved, turning it down and recording
 * that the financial side is settled are the board's, and so is everything about a proposal the board is hosting
 * itself, which names no body at all.
 *
 * Consumed directly through `#[IsGranted(...)]` and by {@see \App\EventListener\Activity\ActivityProposalGuardListener}
 * so the screens and the workflow cannot disagree.
 *
 * @extends Voter<string, ActivityProposal>
 */
final class ActivityProposalVoter extends Voter
{
    public const string VIEW = 'PROPOSAL_VIEW';
    public const string EDIT = 'PROPOSAL_EDIT';
    public const string WITHDRAW = 'PROPOSAL_WITHDRAW';
    public const string DECIDE = 'PROPOSAL_DECIDE';

    private const array ATTRIBUTES = [
        self::VIEW,
        self::EDIT,
        self::WITHDRAW,
        self::DECIDE,
    ];

    public function __construct(private readonly Security $security)
    {
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

        return $subject instanceof ActivityProposal;
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

        $isBoard = $this->security->isGranted(UserRoles::Board->value);
        $isOwner = $this->isOwner(
            $token->getUser(),
            $subject,
        );

        return match ($attribute) {
            self::VIEW => $isBoard || $isOwner,
            // Only a proposal still waiting for a decision may be changed. Once a date is held, changing the dates
            // would mean holding one nobody approved, so the way out is to withdraw and propose again.
            self::EDIT => $subject->getStatus()->isEditableByAuthor() && ($isBoard || $isOwner),
            self::WITHDRAW => $isBoard || $isOwner,
            self::DECIDE => $isBoard,
            default => false,
        };
    }

    /**
     * Whoever is currently installed in the body hosting the activity, or the member who handed the proposal in. A
     * proposal with no body is the board's own, which only the board can be said to own.
     */
    private function isOwner(
        ?object $user,
        ActivityProposal $proposal,
    ): bool {
        if (!$user instanceof User) {
            return false;
        }

        $member = $user->getMember();

        if ($proposal->getCreatedBy()->getLidnr() === $member->getLidnr()) {
            return true;
        }

        return $this->isOrganMember(
            $member,
            $proposal->getOrgan(),
        );
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
}
