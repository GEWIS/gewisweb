<?php

declare(strict_types=1);

namespace App\Security\Photo;

use App\Entity\Decision\Enums\MembershipTypes;
use App\Entity\Decision\Member;
use App\Entity\Photo\Album;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\Photo\MemberTagRepository;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Authorizes viewing a photo {@see Album}.
 *
 * Unpublished albums are never shown through public browsing, not even to the board (a draft mixed in with live albums
 * is confusing); they are managed and previewed only in the photo admin. Published albums are viewable by the board and
 * by API users (the TV screens) and by ordinary/active members. Graduates are gated by the graduate-subtree rule:
 * a graduate may view an album that was made before their membership ended, or any album whose subtree they are
 * tagged in. The recursive
 * subtree check is the fix for the old bug where a graduate tagged in a sub-album could not view the parent. Anonymous
 * and company users cannot browse albums at all.
 *
 * @extends Voter<string, Album>
 */
final class AlbumVoter extends Voter
{
    public const string VIEW = 'ALBUM_VIEW';

    public function __construct(
        private readonly Security $security,
        private readonly MemberTagRepository $memberTagRepository,
    ) {
    }

    #[Override]
    protected function supports(
        string $attribute,
        mixed $subject,
    ): bool {
        return self::VIEW === $attribute
            && $subject instanceof Album;
    }

    #[Override]
    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        return match ($attribute) {
            self::VIEW => $this->canView(
                $subject,
                $token,
            ),
            default => false,
        };
    }

    private function canView(
        Album $album,
        TokenInterface $token,
    ): bool {
        // Unpublished albums are admin-only; public browsing never surfaces them, not even for the board, so a draft is
        // never confused with a live album. The board manages and previews drafts in the photo admin section.
        if (!$album->isPublished()) {
            return false;
        }

        // Board/admins and API users (TV screens) may view any published album.
        if (
            $this->security->isGranted(UserRoles::Board->value)
            || $this->security->isGranted(UserRoles::ApiUser->value)
        ) {
            return true;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            // Anonymous and company users cannot browse albums.
            return false;
        }

        $member = $user->getMember();
        if (MembershipTypes::Graduate === $member->getType()) {
            return $this->graduateMayView(
                $album,
                $member,
            );
        }

        // Ordinary, active and honorary members may view any published album.
        return true;
    }

    /**
     * The graduate-subtree rule: a graduate may view an album dated before their membership ended, or any album whose
     * subtree they are tagged in.
     */
    private function graduateMayView(
        Album $album,
        Member $member,
    ): bool {
        $endsOn = $member->getMembershipEndsOn();
        $startedOn = $album->getStartDateTime();
        if (
            null !== $endsOn
            && null !== $startedOn
            && $startedOn < $endsOn
        ) {
            return true;
        }

        $albumId = $album->getId();
        if (null === $albumId) {
            return false;
        }

        return $this->memberTagRepository->isTaggedInAlbumTree(
            $albumId,
            $member->getLidnr(),
        );
    }
}
