<?php

declare(strict_types=1);

namespace App\Security\Photo;

use App\Entity\Photo\Photo;
use App\Entity\User\Enums\UserRoles;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

use function in_array;

/**
 * Authorizes viewing, downloading, tagging and voting on a {@see Photo}. Viewing and downloading reduce to whether the
 * photo's album is viewable, so that rule (including the graduate-subtree logic) is defined once in {@see AlbumVoter}
 * and delegated to. Tagging and voting additionally require ROLE_MEMBER, which excludes graduates: a graduate may only
 * remove their own member tag (see {@see TagVoter}) and may not tag or vote at all. Voting is refused for a photo that
 * has already been photo of the week, so a past winner cannot be voted for again.
 *
 * @extends Voter<string, Photo>
 */
final class PhotoVoter extends Voter
{
    public const string VIEW = 'PHOTO_VIEW';
    public const string DOWNLOAD = 'PHOTO_DOWNLOAD';
    public const string TAG = 'PHOTO_TAG';
    public const string VOTE = 'PHOTO_VOTE';

    private const array ATTRIBUTES = [
        self::VIEW,
        self::DOWNLOAD,
        self::TAG,
        self::VOTE,
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
        return in_array(
            $attribute,
            self::ATTRIBUTES,
            true,
        )
            && $subject instanceof Photo;
    }

    #[Override]
    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        $viewable = $this->security->isGranted(
            AlbumVoter::VIEW,
            $subject->getAlbum(),
        );

        return match ($attribute) {
            self::VIEW, self::DOWNLOAD => $viewable,
            // Tagging and voting are for members only (graduates are excluded from ROLE_MEMBER) and only on a photo
            // they may view.
            self::TAG => $viewable && $this->security->isGranted(UserRoles::Member->value),
            // Voting adds one rule: a photo that has already been photo of the week may not be voted for again.
            self::VOTE => $viewable
                && $this->security->isGranted(UserRoles::Member->value)
                && null === $subject->getWeeklyPhoto(),
            default => false,
        };
    }
}
