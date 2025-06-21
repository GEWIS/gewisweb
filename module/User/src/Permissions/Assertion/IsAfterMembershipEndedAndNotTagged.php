<?php

declare(strict_types=1);

namespace User\Permissions\Assertion;

use Decision\Model\Enums\MembershipTypes;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;
use Override;
use Photo\Model\Album;
use Photo\Model\Photo;
use Photo\Model\Tag;
use User\Model\User;

/**
 * Assertion to check if when the user is a graduate, that the album they are trying to view is before their membership
 * ended or they are tagged in at least one of the photos in the album.
 */
class IsAfterMembershipEndedAndNotTagged implements AssertionInterface
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function assert(
        Acl $acl,
        ?RoleInterface $role = null,
        ?ResourceInterface $resource = null,
        $privilege = null,
    ): bool {
        if (
            !$role instanceof User
            || (!$resource instanceof Album && !$resource instanceof Photo)
        ) {
            return false;
        }

        // If the member is not a graduate this check should never have been called in the first place, but just make
        // sure that we are only checking graduates.
        if (MembershipTypes::Graduate !== $role->getMember()->getType()) {
            return false;
        }

        if ($resource instanceof Photo) {
            $resource = $resource->getAlbum();
        }

        // It is before the membership ended, allow access
        if ($role->getMember()->getMembershipEndsOn() > $resource->getStartDateTime()) {
            return false;
        }

        // Allow access if the member is tagged in the album
        $tagsInAlbum = $role->getMember()->getTags()->filter(
            static function (Tag $tag) use ($resource) {
                return $resource->getPhotos()->contains($tag->getPhoto());
            },
        );

        return $tagsInAlbum->isEmpty();
    }
}
