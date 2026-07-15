<?php

declare(strict_types=1);

namespace App\Service\Photo;

use App\Entity\Decision\Member;
use App\Entity\Photo\HiddenPhoto;
use App\Entity\Photo\Photo;
use App\Entity\User\Enums\PhotoVisibility;
use App\Entity\User\User;
use App\Repository\Photo\HiddenPhotoRepository;
use App\Repository\Photo\MemberTagRepository;
use App\Repository\Photo\ProfilePhotoRepository;
use App\Repository\User\UserSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

use function array_filter;
use function array_values;

/**
 * The visibility of a member's tagged photos on their own photo page. Others see only what the member's
 * {@see PhotoVisibility} level exposes; the member themselves always sees every photo they are tagged in and can hide
 * or unhide any of them. A photo stays visible in its own album regardless; hiding only affects the member's page.
 */
final readonly class PhotoPrivacyService
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
        private UserSettingsRepository $settingsRepository,
        private HiddenPhotoRepository $hiddenPhotoRepository,
        private MemberTagRepository $memberTagRepository,
        private ProfilePhotoRepository $profilePhotoRepository,
        private ProfilePhotoService $profilePhotoService,
    ) {
    }

    /**
     * Split a member's tagged photos into the set the current viewer may see and the ids that are hidden. The member
     * themselves sees every photo, with the hidden ids flagged so the page can mark them; anyone else sees only what
     * the visibility level exposes and never learns which were hidden.
     *
     * @param Photo[] $photos
     *
     * @return array{visible: Photo[], hidden: array<int, true>}
     */
    public function filterTaggedPhotos(
        Member $member,
        array $photos,
    ): array {
        $hidden = $this->hiddenPhotoRepository->getHiddenPhotoIds($member);

        if ($this->isSelf($member)) {
            return [
                'visible' => $photos,
                'hidden' => $hidden,
            ];
        }

        $level = $this->settingsRepository->find($member->getLidnr())?->getPhotoVisibility()
            ?? PhotoVisibility::HideNone;

        $visible = match ($level) {
            PhotoVisibility::HideNone => $photos,
            PhotoVisibility::HideAll => [],
            PhotoVisibility::HideSelected => array_values(array_filter(
                $photos,
                static fn (Photo $photo): bool => !isset($hidden[(int) $photo->getId()]),
            )),
        };

        return [
            'visible' => $visible,
            'hidden' => [],
        ];
    }

    /**
     * Hide the given photos from the member's own photo page, skipping any they are not tagged in and any already
     * hidden. A newly hidden photo that is the member's profile photo is cleared, so a hidden photo is never left
     * showing as their profile picture.
     *
     * @param Photo[] $photos
     */
    public function hide(
        Member $member,
        array $photos,
    ): void {
        foreach ($photos as $photo) {
            if (
                null === $this->memberTagRepository->findTag(
                    (int) $photo->getId(),
                    $member->getLidnr(),
                )
                || null !== $this->hiddenPhotoRepository->findByMemberAndPhoto(
                    $member,
                    $photo,
                )
            ) {
                continue;
            }

            $hiddenPhoto = new HiddenPhoto();
            $hiddenPhoto->setMember($member);
            $hiddenPhoto->setPhoto($photo);
            $this->entityManager->persist($hiddenPhoto);
        }

        $this->entityManager->flush();
        $this->clearProfilePhotoIfHidden($member);
    }

    /**
     * @param Photo[] $photos
     */
    public function unhide(
        Member $member,
        array $photos,
    ): void {
        foreach ($photos as $photo) {
            $hiddenPhoto = $this->hiddenPhotoRepository->findByMemberAndPhoto(
                $member,
                $photo,
            );
            if (null === $hiddenPhoto) {
                continue;
            }

            $this->entityManager->remove($hiddenPhoto);
        }

        $this->entityManager->flush();
    }

    private function clearProfilePhotoIfHidden(Member $member): void
    {
        $profilePhoto = $this->profilePhotoRepository->getProfilePhotoByLidnr($member->getLidnr());
        if (null === $profilePhoto) {
            return;
        }

        if (
            null === $this->hiddenPhotoRepository->findByMemberAndPhoto(
                $member,
                $profilePhoto->getPhoto(),
            )
        ) {
            return;
        }

        $this->profilePhotoService->removeProfilePhoto($member);
    }

    private function isSelf(Member $member): bool
    {
        $viewer = $this->security->getUser();

        return $viewer instanceof User
            && $viewer->getMember()->getLidnr() === $member->getLidnr();
    }
}
