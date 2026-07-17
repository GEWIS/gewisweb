<?php

declare(strict_types=1);

namespace App\Tests\Service\Photo;

use App\Entity\Decision\Member;
use App\Entity\Photo\Photo;
use App\Entity\User\Enums\PhotoVisibility;
use App\Entity\User\User;
use App\Entity\User\UserSettings;
use App\Repository\Photo\HiddenPhotoRepository;
use App\Repository\Photo\MemberTagRepository;
use App\Repository\Photo\ProfilePhotoRepository;
use App\Repository\User\UserSettingsRepository;
use App\Service\Photo\PhotoPrivacyService;
use App\Service\Photo\ProfilePhotoService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

use function array_map;
use function intval;

/**
 * The member themselves always sees every photo they are tagged in, with the hidden ones flagged; other viewers only
 * see what the member's visibility level exposes and never learn which were hidden.
 */
final class PhotoPrivacyServiceTest extends TestCase
{
    public function testMemberSeesEveryPhotoWithTheHiddenOnesFlagged(): void
    {
        $result = $this->service(
            viewerLidnr: 42,
            level: PhotoVisibility::HideSelected,
            hidden: [2 => true],
        )->filterTaggedPhotos(
            $this->member(42),
            [
                $this->photo(1),
                $this->photo(2),
            ],
        );

        self::assertSame(
            [
                1,
                2,
            ],
            $this->ids($result['visible']),
        );
        self::assertSame(
            [2 => true],
            $result['hidden'],
        );
    }

    public function testOthersSeeEveryPhotoWhenNothingIsHidden(): void
    {
        $result = $this->service(
            viewerLidnr: 99,
            level: PhotoVisibility::HideSelected,
            hidden: [],
        )->filterTaggedPhotos(
            $this->member(42),
            [
                $this->photo(1),
                $this->photo(2),
            ],
        );

        self::assertSame(
            [
                1,
                2,
            ],
            $this->ids($result['visible']),
        );
        self::assertSame(
            [],
            $result['hidden'],
        );
    }

    public function testOthersSeeNoPhotoWhenHidingAll(): void
    {
        $result = $this->service(
            viewerLidnr: 99,
            level: PhotoVisibility::HideAll,
            hidden: [],
        )->filterTaggedPhotos(
            $this->member(42),
            [
                $this->photo(1),
                $this->photo(2),
            ],
        );

        self::assertSame(
            [],
            $result['visible'],
        );
        self::assertSame(
            [],
            $result['hidden'],
        );
    }

    public function testOthersSeeOnlyTheUnhiddenPhotosWhenHidingSelected(): void
    {
        $result = $this->service(
            viewerLidnr: 99,
            level: PhotoVisibility::HideSelected,
            hidden: [2 => true],
        )->filterTaggedPhotos(
            $this->member(42),
            [
                $this->photo(1),
                $this->photo(2),
                $this->photo(3),
            ],
        );

        self::assertSame(
            [
                1,
                3,
            ],
            $this->ids($result['visible']),
        );
        self::assertSame(
            [],
            $result['hidden'],
        );
    }

    /**
     * @param array<int, true> $hidden the ids to treat as hidden, as a set
     */
    private function service(
        int $viewerLidnr,
        PhotoVisibility $level,
        array $hidden,
    ): PhotoPrivacyService {
        $viewer = self::createStub(User::class);
        $viewer->method('getMember')->willReturn($this->member($viewerLidnr));

        $security = self::createStub(Security::class);
        $security->method('isGranted')->willReturn(false);
        $security->method('getUser')->willReturn($viewer);

        $settings = self::createStub(UserSettings::class);
        $settings->method('getPhotoVisibility')->willReturn($level);
        $settingsRepository = self::createStub(UserSettingsRepository::class);
        $settingsRepository->method('find')->willReturn($settings);

        $hiddenRepository = self::createStub(HiddenPhotoRepository::class);
        $hiddenRepository->method('getHiddenPhotoIds')->willReturn($hidden);

        // The profile-photo service is final and only used by the write paths, so a real instance over stubs is enough
        // for the read path under test here.
        $profilePhotoService = new ProfilePhotoService(
            self::createStub(MemberTagRepository::class),
            self::createStub(ProfilePhotoRepository::class),
            self::createStub(HiddenPhotoRepository::class),
            self::createStub(EntityManagerInterface::class),
        );

        return new PhotoPrivacyService(
            $security,
            self::createStub(EntityManagerInterface::class),
            $settingsRepository,
            $hiddenRepository,
            self::createStub(MemberTagRepository::class),
            self::createStub(ProfilePhotoRepository::class),
            $profilePhotoService,
        );
    }

    private function member(int $lidnr): Member
    {
        $member = self::createStub(Member::class);
        $member->method('getLidnr')->willReturn($lidnr);

        return $member;
    }

    private function photo(int $id): Photo
    {
        $photo = self::createStub(Photo::class);
        $photo->method('getId')->willReturn($id);

        return $photo;
    }

    /**
     * @param Photo[] $photos
     *
     * @return int[]
     */
    private function ids(array $photos): array
    {
        return array_map(
            static fn (Photo $photo): int => intval($photo->getId()),
            $photos,
        );
    }
}
