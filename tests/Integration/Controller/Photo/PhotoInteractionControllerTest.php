<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Photo;

use App\Controller\Photo\PhotoInteractionController;
use App\DataFixtures\Photo\PhotoFixture;
use App\Entity\Photo\MemberTag;
use App\Entity\Photo\Photo;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\Photo\AlbumRepository;
use App\Repository\Photo\MemberTagRepository;
use App\Repository\Photo\PhotoRepository;
use App\Tests\Integration\DatabaseTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use function json_decode;

use const JSON_THROW_ON_ERROR;

/**
 * The tag, vote and profile actions, invoked directly. The point of interest is the graduate rule: a graduate may only
 * remove a member tag that concerns themselves, and may not create tags or vote at all; a regular member (like the
 * board) may tag, vote, and remove any tag.
 */
final class PhotoInteractionControllerTest extends DatabaseTestCase
{
    /** The seeded graduate's membership ended before the albums existed, so they view them only via their tag. */
    private const string BEFORE_ALBUMS = '2020-01-01 00:00:00';

    private const int MEMBER = 8030;
    private const int OTHER_MEMBER = 8031;
    private const int BOARD = 8025;

    public function testAMemberCanTagAnotherMember(): void
    {
        $this->authenticate(
            self::MEMBER,
            UserRoles::Member,
        );

        $response = $this->controller()->tag(
            (int) $this->tripPhoto()->getId(),
            $this->payload(['type' => 'member', 'id' => self::OTHER_MEMBER]),
        );

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );
        self::assertTrue($this->decode($response)['success']);
    }

    public function testTaggingAMemberTwiceConflicts(): void
    {
        $this->authenticate(
            self::MEMBER,
            UserRoles::Member,
        );

        // The member is already tagged in the trip photo by the fixtures.
        $response = $this->controller()->tag(
            (int) $this->tripPhoto()->getId(),
            $this->payload(['type' => 'member', 'id' => self::MEMBER]),
        );

        self::assertSame(
            Response::HTTP_CONFLICT,
            $response->getStatusCode(),
        );
    }

    public function testAMemberCanRemoveTheirOwnTag(): void
    {
        $this->authenticate(
            self::MEMBER,
            UserRoles::Member,
        );
        $tag = $this->tagOf(
            $this->tripPhoto(),
            self::MEMBER,
        );

        $response = $this->controller()->removeTag((int) $tag->getId());

        self::assertTrue($this->decode($response)['success']);
    }

    public function testAMemberCanRemoveSomeoneElsesTag(): void
    {
        $this->authenticate(
            self::MEMBER,
            UserRoles::Member,
        );
        $graduateTag = $this->graduateTag();

        $response = $this->controller()->removeTag((int) $graduateTag->getId());

        self::assertTrue($this->decode($response)['success']);
    }

    public function testTheBoardCanRemoveAnyTag(): void
    {
        $this->authenticate(
            self::BOARD,
            UserRoles::Board,
        );
        $graduateTag = $this->graduateTag();

        $response = $this->controller()->removeTag((int) $graduateTag->getId());

        self::assertTrue($this->decode($response)['success']);
    }

    public function testAGraduateCanRemoveTheirOwnTag(): void
    {
        // No view/membership setup needed: removing a tag is decided purely on the tag's ownership.
        $this->authenticate(
            PhotoFixture::GRADUATE_TAGGED_IN_SUBTREE,
            UserRoles::Graduate,
        );
        $graduateTag = $this->graduateTag();

        $response = $this->controller()->removeTag((int) $graduateTag->getId());

        self::assertTrue($this->decode($response)['success']);
    }

    public function testAGraduateCannotRemoveSomeoneElsesTag(): void
    {
        $this->authenticate(
            PhotoFixture::GRADUATE_TAGGED_IN_SUBTREE,
            UserRoles::Graduate,
        );
        $othersTag = $this->tagOf(
            $this->graduateTag()->getPhoto(),
            self::MEMBER,
        );

        $this->expectException(AccessDeniedException::class);
        $this->controller()->removeTag((int) $othersTag->getId());
    }

    public function testAGraduateCannotTag(): void
    {
        // Let the graduate view the photo so we reach the tag check rather than a not-found.
        $this->pinMembershipEnd(
            PhotoFixture::GRADUATE_TAGGED_IN_SUBTREE,
            self::BEFORE_ALBUMS,
        );
        $photoId = (int) $this->graduateTag()->getPhoto()->getId();
        $this->authenticate(
            PhotoFixture::GRADUATE_TAGGED_IN_SUBTREE,
            UserRoles::Graduate,
        );

        $this->expectException(AccessDeniedException::class);
        $this->controller()->tag(
            $photoId,
            $this->payload(['type' => 'member', 'id' => self::OTHER_MEMBER]),
        );
    }

    public function testAMemberCanVoteAndVotingIsIdempotent(): void
    {
        $this->authenticate(
            self::MEMBER,
            UserRoles::Member,
        );
        $photoId = (int) $this->graduateTag()->getPhoto()->getId();

        self::assertTrue($this->decode($this->controller()->vote($photoId))['success']);
        // Voting again must not fail or create a second vote.
        self::assertTrue($this->decode($this->controller()->vote($photoId))['success']);
    }

    public function testAGraduateCannotVote(): void
    {
        $this->pinMembershipEnd(
            PhotoFixture::GRADUATE_TAGGED_IN_SUBTREE,
            self::BEFORE_ALBUMS,
        );
        $photoId = (int) $this->graduateTag()->getPhoto()->getId();
        $this->authenticate(
            PhotoFixture::GRADUATE_TAGGED_IN_SUBTREE,
            UserRoles::Graduate,
        );

        $this->expectException(AccessDeniedException::class);
        $this->controller()->vote($photoId);
    }

    public function testDetailsListsTagsAndTheViewersOwnAbilities(): void
    {
        $this->authenticate(
            self::MEMBER,
            UserRoles::Member,
        );
        $photoId = (int) $this->graduateTag()->getPhoto()->getId();

        $details = $this->decode($this->controller()->details($photoId));

        // The dinner photo is tagged with two members (the graduate and this member) and one organ.
        self::assertCount(
            2,
            $details['memberTags'],
        );
        self::assertCount(
            1,
            $details['organTags'],
        );
        self::assertTrue($details['canTag']);
        self::assertTrue($details['canVote']);
        self::assertTrue($details['taggedSelf']);
        // The dinner photo is the (hidden) photo of the week in the seed, so the viewer can badge it.
        self::assertNotNull($details['photoOfTheWeek']);
    }

    private function controller(): PhotoInteractionController
    {
        return self::getContainer()->get(PhotoInteractionController::class);
    }

    private function tripPhoto(): Photo
    {
        $album = self::getContainer()->get(AlbumRepository::class)->findOneBy(['name' => 'Trip 2024']);
        self::assertNotNull($album);
        $photos = self::getContainer()->get(PhotoRepository::class)->getAlbumPhotos($album);
        self::assertNotEmpty($photos);

        return $photos[0];
    }

    /**
     * The member tag on the dinner photo that concerns the seeded graduate, used as "a tag I own" for the graduate and
     * "someone else's tag" for everyone else.
     */
    private function graduateTag(): MemberTag
    {
        $tags = self::getContainer()->get(MemberTagRepository::class)
            ->getTagsByLidnr(PhotoFixture::GRADUATE_TAGGED_IN_SUBTREE);
        self::assertNotEmpty($tags);

        return $tags[0];
    }

    private function tagOf(
        Photo $photo,
        int $lidnr,
    ): MemberTag {
        $tag = self::getContainer()->get(MemberTagRepository::class)->findTag(
            (int) $photo->getId(),
            $lidnr,
        );
        self::assertInstanceOf(
            MemberTag::class,
            $tag,
            'The seed is expected to contain the tag.',
        );

        return $tag;
    }

    /**
     * @param array<string, int|string> $parameters
     */
    private function payload(array $parameters): Request
    {
        return new Request(request: $parameters);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response): array
    {
        return json_decode(
            (string) $response->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }

    private function pinMembershipEnd(
        int $lidnr,
        string $endsOn,
    ): void {
        $this->entityManager->getConnection()->update(
            'Member',
            ['membershipEndsOn' => $endsOn],
            ['lidnr' => $lidnr],
        );
        $this->entityManager->clear();
    }

    private function authenticate(
        int $lidnr,
        UserRoles $role,
    ): void {
        $user = $this->entityManager->getRepository(User::class)->find($lidnr);
        self::assertInstanceOf(
            User::class,
            $user,
            'The seed is expected to contain a user for the member.',
        );

        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $user,
                'main',
                [$role->value],
            ),
        );
    }
}
