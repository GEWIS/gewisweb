<?php

declare(strict_types=1);

namespace App\Service\Photo;

use App\Entity\Decision\Member;
use App\Repository\Photo\MemberTagRepository;

/**
 * Removes every existing photo tag of a member on request (the "remove all tags of me" action that accompanies the
 * photo-tagging opt-out). Preventing new tags lives in {@see TagService::addMemberTag()}; this is the retroactive
 * counterpart.
 */
final readonly class MemberTagPurgeService
{
    public function __construct(
        private MemberTagRepository $memberTagRepository,
        private TagService $tagService,
        private ProfilePhotoService $profilePhotoService,
    ) {
    }

    /**
     * Delete all of the member's tags and clear their profile photo. A profile photo must be one the member is tagged
     * in, so once the tags are gone it is orphaned and must be removed too.
     */
    public function purgeTagsOf(Member $member): void
    {
        foreach ($this->memberTagRepository->getTagsByLidnr($member->getLidnr()) as $tag) {
            $this->tagService->removeTag($tag);
        }

        $this->profilePhotoService->removeProfilePhoto($member);
    }
}
