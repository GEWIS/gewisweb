<?php

declare(strict_types=1);

namespace App\Security\Photo;

use App\Entity\Photo\MemberTag;
use App\Entity\Photo\Tag;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Authorizes removing a photo {@see Tag}. Any member -- which the board and admins are, through the role hierarchy --
 * may remove any tag. A graduate is the exception: they may remove only a member tag that concerns themselves, which is
 * the sole tag action they can take (they may not remove anyone else's tag or an organ tag, create tags, or vote).
 *
 * @extends Voter<string, Tag>
 */
final class TagVoter extends Voter
{
    public const string REMOVE = 'PHOTO_TAG_REMOVE';

    public function __construct(
        private readonly Security $security,
    ) {
    }

    #[Override]
    protected function supports(
        string $attribute,
        mixed $subject,
    ): bool {
        return self::REMOVE === $attribute
            && $subject instanceof Tag;
    }

    #[Override]
    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        // A member (which the board and admins are, through the role hierarchy) may remove any tag. Only a graduate is
        // held to their own member tag, and they are the only authenticated non-member who reaches this action.
        if ($this->security->isGranted(UserRoles::Member->value)) {
            return true;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return $subject instanceof MemberTag
            && $subject->getMember()->getLidnr() === $user->getMember()->getLidnr();
    }
}
