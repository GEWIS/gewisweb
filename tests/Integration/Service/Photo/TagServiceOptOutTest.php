<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Photo;

use App\Entity\Photo\MemberTag;
use App\Entity\Photo\Photo;
use App\Entity\User\UserSettings;
use App\Repository\Photo\AlbumRepository;
use App\Repository\Photo\PhotoRepository;
use App\Repository\User\UserRepository;
use App\Service\Photo\TagService;
use App\Tests\Integration\DatabaseTestCase;

/**
 * A member who opts out of being tagged can no longer be tagged; the enforcement lives in the service so it holds for
 * every caller. The seeded member 8031 is not tagged on the Trip photo, so it is a clean subject for both cases.
 */
final class TagServiceOptOutTest extends DatabaseTestCase
{
    private const int MEMBER = 8031;

    public function testAMemberWhoHasNotOptedOutCanBeTagged(): void
    {
        $tag = $this->tagService()->addMemberTag(
            $this->tripPhoto(),
            self::MEMBER,
            null,
            null,
        );

        self::assertInstanceOf(
            MemberTag::class,
            $tag,
        );
    }

    public function testAMemberWhoOptedOutCannotBeTagged(): void
    {
        $this->optOut(self::MEMBER);

        $tag = $this->tagService()->addMemberTag(
            $this->tripPhoto(),
            self::MEMBER,
            null,
            null,
        );

        self::assertNull($tag);
    }

    private function optOut(int $lidnr): void
    {
        $user = self::getContainer()->get(UserRepository::class)->find($lidnr);
        self::assertNotNull($user);

        $settings = new UserSettings($user);
        $settings->setPhotoTaggingOptOut(true);
        $this->entityManager->persist($settings);
        $this->entityManager->flush();
    }

    private function tagService(): TagService
    {
        return self::getContainer()->get(TagService::class);
    }

    private function tripPhoto(): Photo
    {
        $album = self::getContainer()->get(AlbumRepository::class)->findOneBy(['name' => 'Trip 2024']);
        self::assertNotNull($album);
        $photos = self::getContainer()->get(PhotoRepository::class)->getAlbumPhotos($album);
        self::assertNotEmpty($photos);

        return $photos[0];
    }
}
