<?php

declare(strict_types=1);

namespace App\Service\Photo;

use App\Entity\Decision\Member;
use App\Entity\Photo\Photo;
use App\Entity\Photo\ProfilePhoto;
use App\Repository\Photo\MemberTagRepository;
use App\Repository\Photo\ProfilePhotoRepository;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

/**
 * A member may pick one of the photos they are tagged in as their profile photo. A member has at most one profile
 * photo, so setting a new one replaces the old. Setting one explicitly keeps it for a year.
 */
final readonly class ProfilePhotoService
{
    public function __construct(
        private MemberTagRepository $memberTagRepository,
        private ProfilePhotoRepository $profilePhotoRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Set the member's profile photo, replacing any existing one. Returns false (and changes nothing) when the member
     * is not tagged in the photo, so a profile photo can only ever be one the member appears in.
     */
    public function setProfilePhoto(
        Photo $photo,
        Member $member,
    ): bool {
        if (
            null === $this->memberTagRepository->findTag(
                (int) $photo->getId(),
                $member->getLidnr(),
            )
        ) {
            return false;
        }

        $this->removeProfilePhoto($member);

        $profilePhoto = new ProfilePhoto();
        $profilePhoto->setPhoto($photo);
        $profilePhoto->setMember($member);
        $profilePhoto->setDateTime(new DateTime()->add(new DateInterval('P1Y')));
        $profilePhoto->setExplicit(true);

        $this->entityManager->persist($profilePhoto);
        $this->entityManager->flush();

        return true;
    }

    public function removeProfilePhoto(Member $member): void
    {
        $existing = $this->profilePhotoRepository->getProfilePhotoByLidnr($member->getLidnr());
        if (null === $existing) {
            return;
        }

        $this->entityManager->remove($existing);
        $this->entityManager->flush();
    }
}
