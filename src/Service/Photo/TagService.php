<?php

declare(strict_types=1);

namespace App\Service\Photo;

use App\Entity\Photo\MemberTag;
use App\Entity\Photo\OrganTag;
use App\Entity\Photo\Photo;
use App\Entity\Photo\Tag;
use App\Repository\Decision\MemberRepository;
use App\Repository\Decision\OrganRepository;
use App\Repository\Photo\MemberTagRepository;
use App\Repository\Photo\OrganTagRepository;
use App\Repository\User\UserSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Creates and removes photo tags. Authorization (who may tag or remove which tag, including the graduate rule) lives in
 * {@see \App\Security\Photo\PhotoVoter} and {@see \App\Security\Photo\TagVoter}; this service only enforces the data
 * rules: the member or organ must exist and must not already be tagged on the photo.
 */
final readonly class TagService
{
    public function __construct(
        private MemberRepository $memberRepository,
        private OrganRepository $organRepository,
        private MemberTagRepository $memberTagRepository,
        private OrganTagRepository $organTagRepository,
        private UserSettingsRepository $userSettingsRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Tag a member on a photo, optionally at a point. Returns null when the member does not exist, has opted out of
     * being tagged, or is already tagged on the photo, so tagging stays idempotent. Invalid coordinates make
     * {@see Tag::setPosition()} throw.
     */
    public function addMemberTag(
        Photo $photo,
        int $lidnr,
        ?float $x,
        ?float $y,
    ): ?MemberTag {
        $member = $this->memberRepository->find($lidnr);
        if (
            null === $member
            || null !== $this->memberTagRepository->findTag(
                (int) $photo->getId(),
                $lidnr,
            )
            || ($this->userSettingsRepository->find($lidnr)?->getPhotoTaggingOptOut() ?? false)
        ) {
            return null;
        }

        $tag = new MemberTag();
        $tag->setPhoto($photo);
        $tag->setMember($member);
        $tag->setPosition(
            $x,
            $y,
        );

        $this->entityManager->persist($tag);
        $this->entityManager->flush();

        return $tag;
    }

    /**
     * Tag an organ on a photo, optionally at a point. Returns null when the organ is not active or is already tagged.
     */
    public function addOrganTag(
        Photo $photo,
        int $organId,
        ?float $x,
        ?float $y,
    ): ?OrganTag {
        $organ = $this->organRepository->findActiveById($organId);
        if (
            null === $organ
            || null !== $this->organTagRepository->findTag(
                (int) $photo->getId(),
                $organId,
            )
        ) {
            return null;
        }

        $tag = new OrganTag();
        $tag->setPhoto($photo);
        $tag->setOrgan($organ);
        $tag->setPosition(
            $x,
            $y,
        );

        $this->entityManager->persist($tag);
        $this->entityManager->flush();

        return $tag;
    }

    public function removeTag(Tag $tag): void
    {
        $this->entityManager->remove($tag);
        $this->entityManager->flush();
    }
}
