<?php

declare(strict_types=1);

namespace App\Tests\Integration\Security\Photo;

use App\DataFixtures\Photo\PhotoFixture;
use App\Entity\Photo\Album;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\Photo\AlbumRepository;
use App\Repository\Photo\MemberTagRepository;
use App\Security\Photo\AlbumVoter;
use App\Tests\Integration\DatabaseTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The graduate-subtree regression matrix. The seed's Gala album has a Dinner and an Afterparty sub-album; one
 * graduate is tagged in each. Because the fixture randomises a graduate's membership-end date, each test pins it
 * explicitly: a date in the past makes the (recent) albums fall after the membership ended, activating the
 * tagged-in-subtree rule; a future date makes them fall before it. Ordinary members, the board, API and anonymous
 * requests round out the matrix.
 */
final class AlbumVoterTest extends DatabaseTestCase
{
    private const string BEFORE_ALBUMS = '2020-01-01 00:00:00';
    private const string AFTER_ALBUMS = '2099-01-01 00:00:00';

    public function testGraduateTaggedInASubAlbumMayViewTheParentAlbum(): void
    {
        // The graduate-subtree case: tagged in Dinner (a sub-album), the graduate may view the parent Gala album.
        $this->pinMembershipEnd(
            PhotoFixture::GRADUATE_TAGGED_IN_SUBTREE,
            self::BEFORE_ALBUMS,
        );
        $gala = $this->parentOfAlbumTaggingGraduate(PhotoFixture::GRADUATE_TAGGED_IN_SUBTREE);
        $this->authenticateGraduate(PhotoFixture::GRADUATE_TAGGED_IN_SUBTREE);

        self::assertTrue($this->checker()->isGranted(AlbumVoter::VIEW, $gala));
    }

    public function testGraduateTaggedInASubAlbumMayViewThatSubAlbum(): void
    {
        $this->pinMembershipEnd(
            PhotoFixture::GRADUATE_TAGGED_IN_SUBTREE,
            self::BEFORE_ALBUMS,
        );
        $dinner = $this->albumTaggingGraduate(PhotoFixture::GRADUATE_TAGGED_IN_SUBTREE);
        $this->authenticateGraduate(PhotoFixture::GRADUATE_TAGGED_IN_SUBTREE);

        self::assertTrue($this->checker()->isGranted(AlbumVoter::VIEW, $dinner));
    }

    public function testGraduateTaggedInASiblingSubAlbumMayNotViewTheOtherSubAlbum(): void
    {
        // Tagged only in Afterparty, the graduate may not view the sibling Dinner sub-album (not in Dinner's subtree).
        $this->pinMembershipEnd(
            PhotoFixture::GRADUATE_TAGGED_IN_OTHER_SUBALBUM,
            self::BEFORE_ALBUMS,
        );
        $dinner = $this->albumTaggingGraduate(PhotoFixture::GRADUATE_TAGGED_IN_SUBTREE);
        $this->authenticateGraduate(PhotoFixture::GRADUATE_TAGGED_IN_OTHER_SUBALBUM);

        self::assertFalse($this->checker()->isGranted(AlbumVoter::VIEW, $dinner));
    }

    public function testGraduateTaggedNowhereMayNotViewAnAlbumMadeAfterTheirMembershipEnded(): void
    {
        $this->pinMembershipEnd(
            PhotoFixture::GRADUATE_TAGGED_NOWHERE,
            self::BEFORE_ALBUMS,
        );
        $gala = $this->parentOfAlbumTaggingGraduate(PhotoFixture::GRADUATE_TAGGED_IN_SUBTREE);
        $this->authenticateGraduate(PhotoFixture::GRADUATE_TAGGED_NOWHERE);

        self::assertFalse($this->checker()->isGranted(AlbumVoter::VIEW, $gala));
    }

    public function testGraduateMayViewAnAlbumMadeBeforeTheirMembershipEndedEvenWithoutATag(): void
    {
        $this->pinMembershipEnd(
            PhotoFixture::GRADUATE_TAGGED_NOWHERE,
            self::AFTER_ALBUMS,
        );
        $gala = $this->parentOfAlbumTaggingGraduate(PhotoFixture::GRADUATE_TAGGED_IN_SUBTREE);
        $this->authenticateGraduate(PhotoFixture::GRADUATE_TAGGED_NOWHERE);

        self::assertTrue($this->checker()->isGranted(AlbumVoter::VIEW, $gala));
    }

    public function testOrdinaryMemberMayViewAPublishedAlbum(): void
    {
        $gala = $this->parentOfAlbumTaggingGraduate(PhotoFixture::GRADUATE_TAGGED_IN_SUBTREE);
        $this->authenticate(
            8030,
            [UserRoles::Member->value],
        );

        self::assertTrue($this->checker()->isGranted(AlbumVoter::VIEW, $gala));
    }

    public function testMemberMayNotViewAnUnpublishedAlbum(): void
    {
        $this->authenticate(
            8030,
            [UserRoles::Member->value],
        );

        self::assertFalse($this->checker()->isGranted(AlbumVoter::VIEW, $this->draftAlbum()));
    }

    public function testBoardMayNotViewAnUnpublishedAlbum(): void
    {
        $this->authenticate(
            8025,
            [UserRoles::Board->value],
        );

        // Unpublished albums are admin-only; the board browses them in the photo admin, not through the album voter.
        self::assertFalse($this->checker()->isGranted(AlbumVoter::VIEW, $this->draftAlbum()));
    }

    public function testAnonymousMayNotViewAPublishedAlbum(): void
    {
        $gala = $this->parentOfAlbumTaggingGraduate(PhotoFixture::GRADUATE_TAGGED_IN_SUBTREE);
        // No token set.

        self::assertFalse($this->checker()->isGranted(AlbumVoter::VIEW, $gala));
    }

    private function checker(): AuthorizationCheckerInterface
    {
        return self::getContainer()->get(AuthorizationCheckerInterface::class);
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
        // Detach so the pinned value is re-read on the next query (mirrors the DrawManager tests).
        $this->entityManager->clear();
    }

    private function albumTaggingGraduate(int $lidnr): Album
    {
        $repository = self::getContainer()->get(MemberTagRepository::class);
        $tags = $repository->getTagsByLidnr($lidnr);
        self::assertNotEmpty(
            $tags,
            'The graduate is expected to be tagged in the seed.',
        );

        return $tags[0]->getPhoto()->getAlbum();
    }

    private function parentOfAlbumTaggingGraduate(int $lidnr): Album
    {
        $parent = $this->albumTaggingGraduate($lidnr)->getParent();
        self::assertInstanceOf(
            Album::class,
            $parent,
            'The tagged sub-album is expected to have a parent.',
        );

        return $parent;
    }

    private function draftAlbum(): Album
    {
        $album = self::getContainer()->get(AlbumRepository::class)->findOneBy(['published' => false]);
        self::assertInstanceOf(
            Album::class,
            $album,
            'The seed is expected to contain an unpublished album.',
        );

        return $album;
    }

    private function authenticateGraduate(int $lidnr): void
    {
        $this->authenticate(
            $lidnr,
            [UserRoles::Graduate->value],
        );
    }

    /**
     * @param list<string> $roles
     */
    private function authenticate(
        int $lidnr,
        array $roles,
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
                $roles,
            ),
        );
    }
}
