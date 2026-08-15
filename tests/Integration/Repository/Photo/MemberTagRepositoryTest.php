<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository\Photo;

use App\Entity\Decision\Member;
use App\Entity\Photo\MemberTag;
use App\Repository\Photo\MemberTagRepository;
use App\Tests\Integration\DatabaseTestCase;

use function array_map;
use function array_unique;
use function array_values;
use function count;

/**
 * The birthday panel rotates between the members it can show a photo of, so the query behind it has to answer with at
 * most one photo per member and leave out anybody nobody has ever tagged.
 */
final class MemberTagRepositoryTest extends DatabaseTestCase
{
    public function testAtMostOnePhotoPerMember(): void
    {
        $members = $this->taggedMembers();
        self::assertGreaterThan(
            1,
            count($members),
            'The seed is expected to contain several tagged members.',
        );

        $tags = $this->repository()->findMostRecentTagPerMember($members);
        $lidnrs = array_map(
            static fn (MemberTag $tag): int => $tag->getMember()->getLidnr(),
            $tags,
        );

        self::assertSame(
            $lidnrs,
            array_unique($lidnrs),
        );
    }

    public function testTheNewestPhotoOfAMemberWins(): void
    {
        $member = $this->taggedMembers()[0];

        $tags = $this->repository()->findMostRecentTagPerMember([$member]);
        self::assertCount(
            1,
            $tags,
        );

        $newest = $tags[0]->getPhoto()->getDateTime();

        foreach ($this->repository()->getTagsByLidnr($member->getLidnr()) as $tag) {
            self::assertLessThanOrEqual(
                $newest,
                $tag->getPhoto()->getDateTime(),
            );
        }
    }

    public function testAMemberNobodyHasTaggedIsLeftOut(): void
    {
        $untagged = null;

        foreach ($this->entityManager->getRepository(Member::class)->findAll() as $member) {
            if ($this->repository()->hasTags($member->getLidnr())) {
                continue;
            }

            $untagged = $member;
            break;
        }

        self::assertInstanceOf(
            Member::class,
            $untagged,
            'The seed is expected to contain a member nobody has tagged.',
        );
        self::assertSame(
            [],
            $this->repository()->findMostRecentTagPerMember([$untagged]),
        );
    }

    public function testNoMembersMeansNoQuery(): void
    {
        self::assertSame(
            [],
            $this->repository()->findMostRecentTagPerMember([]),
        );
    }

    /**
     * @return list<Member>
     */
    private function taggedMembers(): array
    {
        $members = [];

        foreach ($this->repository()->findAll() as $tag) {
            $members[$tag->getMember()->getLidnr()] = $tag->getMember();
        }

        return array_values($members);
    }

    private function repository(): MemberTagRepository
    {
        return self::getContainer()->get(MemberTagRepository::class);
    }
}
