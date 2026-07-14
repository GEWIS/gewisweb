<?php

declare(strict_types=1);

namespace App\Tests\Integration\Entity\Photo;

use App\DataFixtures\Photo\PhotoFixture;
use App\Entity\Decision\Member;
use App\Entity\Decision\Organ;
use App\Entity\Photo\MemberTag;
use App\Entity\Photo\OrganTag;
use App\Entity\Photo\Photo;
use App\Entity\Photo\Tag;
use App\Repository\Photo\MemberTagRepository;
use App\Repository\Photo\TagRepository;
use App\Tests\Integration\DatabaseTestCase;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

use function array_filter;

/**
 * Exercises the {@see Tag} single-table-inheritance mapping against the seeded photo tree: that member and organ tags
 * coexist in one table, that each subtype's uniqueness holds independently (MariaDB's distinct-NULL behaviour), that
 * point-in-image positions round-trip, and that the member-tags inverse (the GDPR entry point) never surfaces organ
 * tags.
 */
final class TagStiTest extends DatabaseTestCase
{
    public function testMemberAndOrganTagsCoexistOnOnePhoto(): void
    {
        $photo = $this->dinnerPhoto();

        $tags = $this->tagRepository()->findByPhoto((int) $photo->getId());
        $members = array_filter(
            $tags,
            static fn (Tag $tag): bool => $tag instanceof MemberTag,
        );
        $organs = array_filter(
            $tags,
            static fn (Tag $tag): bool => $tag instanceof OrganTag,
        );

        // The seed tags the dinner photo with two members (a graduate + an ordinary member) and one organ.
        self::assertCount(
            2,
            $members,
            'The dinner photo is seeded with two member tags.',
        );
        self::assertCount(
            1,
            $organs,
            'The dinner photo is seeded with one organ tag.',
        );
    }

    public function testPointInImagePositionRoundTrips(): void
    {
        $tags = $this->memberTagRepository()->getTagsByLidnr(PhotoFixture::GRADUATE_TAGGED_IN_SUBTREE);
        self::assertNotEmpty(
            $tags,
            'The in-subtree graduate is expected to be tagged in the seed.',
        );
        $tag = $tags[0];

        self::assertTrue($tag->hasPosition());
        self::assertEqualsWithDelta(
            0.52,
            $tag->getPositionX(),
            0.0001,
        );
        self::assertEqualsWithDelta(
            0.41,
            $tag->getPositionY(),
            0.0001,
        );
    }

    public function testDuplicateMemberTagViolatesItsUniqueConstraint(): void
    {
        $photo = $this->dinnerPhoto();

        // Member 8030 is already tagged on the dinner photo; a second row is a (photo_id, member_id) collision. Raw
        // DBAL keeps the EntityManager usable and is rolled back with the test.
        $this->expectException(UniqueConstraintViolationException::class);
        $this->entityManager->getConnection()->insert(
            'Tag',
            [
                'dtype' => 'member',
                'photo_id' => $photo->getId(),
                'member_id' => 8030,
            ],
        );
    }

    public function testDuplicateOrganTagViolatesItsUniqueConstraint(): void
    {
        $photo = $this->dinnerPhoto();
        $organ = $this->entityManager->getRepository(Organ::class)->findOneBy(['abbr' => 'GETÉST']);
        self::assertInstanceOf(
            Organ::class,
            $organ,
            'The seed is expected to contain the GETÉST organ.',
        );

        // The GETÉST organ is already tagged on the dinner photo; a second row is a (photo_id, organ_id) collision.
        $this->expectException(UniqueConstraintViolationException::class);
        $this->entityManager->getConnection()->insert(
            'Tag',
            [
                'dtype' => 'organ',
                'photo_id' => $photo->getId(),
                'organ_id' => $organ->getId(),
            ],
        );
    }

    public function testMemberAndOrganTagOnTheSamePhotoDoNotCollide(): void
    {
        $photo = $this->dinnerPhoto();

        // Both a member tag and an organ tag already reference the dinner photo in the seed, proving the two unique
        // indexes coexist (each other's discriminating column is NULL, which MariaDB treats as distinct).
        $organTags = $this->entityManager->getRepository(OrganTag::class)->findBy(['photo' => $photo->getId()]);
        $memberTags = $this->entityManager->getRepository(MemberTag::class)->findBy(['photo' => $photo->getId()]);

        self::assertNotEmpty($organTags);
        self::assertNotEmpty($memberTags);
    }

    public function testMemberTagsInverseExcludesOrganTags(): void
    {
        // Member 8030 is tagged in three photos in the seed; the inverse must return MemberTags only (the GDPR walk).
        $member = $this->entityManager->getRepository(Member::class)->find(8030);
        self::assertInstanceOf(
            Member::class,
            $member,
            'The seed is expected to contain member 8030.',
        );

        // The inverse is typed Collection<MemberTag>; the seed tags 8030 in exactly three photos (dinner, trip,
        // draft). An organ tag leaking through the member inverse would push the count above three.
        self::assertCount(
            3,
            $member->getTags(),
        );
    }

    private function dinnerPhoto(): Photo
    {
        // The in-subtree graduate is tagged on exactly the dinner photo, so reach it through that tag.
        $tags = $this->memberTagRepository()->getTagsByLidnr(PhotoFixture::GRADUATE_TAGGED_IN_SUBTREE);
        self::assertNotEmpty(
            $tags,
            'The in-subtree graduate is expected to be tagged in the seed.',
        );

        return $tags[0]->getPhoto();
    }

    private function tagRepository(): TagRepository
    {
        return self::getContainer()->get(TagRepository::class);
    }

    private function memberTagRepository(): MemberTagRepository
    {
        return self::getContainer()->get(MemberTagRepository::class);
    }
}
