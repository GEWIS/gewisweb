<?php

declare(strict_types=1);

namespace App\Security\Photo;

use App\Entity\Photo\Photo;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

use function in_array;

/**
 * Authorizes viewing and downloading a {@see Photo}. Both reduce to whether the photo's album is viewable, so the rule
 * (including the #1658 graduate-subtree logic) is defined once in {@see AlbumVoter}, and this voter delegates to it.
 *
 * @extends Voter<string, Photo>
 */
final class PhotoVoter extends Voter
{
    public const string VIEW = 'PHOTO_VIEW';
    public const string DOWNLOAD = 'PHOTO_DOWNLOAD';

    private const array ATTRIBUTES = [
        self::VIEW,
        self::DOWNLOAD,
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
        return match ($attribute) {
            self::VIEW, self::DOWNLOAD => $this->security->isGranted(
                AlbumVoter::VIEW,
                $subject->getAlbum(),
            ),
            default => false,
        };
    }
}
