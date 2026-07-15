<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Photo;

use App\Entity\Decision\AssociationYear;
use App\Entity\Photo\Album;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\Photo\AlbumRepository;
use App\Service\Photo\AlbumService;
use App\Tests\Integration\DatabaseTestCase;
use DateTime;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use function array_map;
use function array_merge;
use function array_values;

/**
 * The album browsing service must only ever surface albums the current user may view: members see published albums,
 * board and admins also see unpublished ones, and every album (including sub-albums) passes the album voter.
 */
final class AlbumServiceTest extends DatabaseTestCase
{
    public function testMemberSeesPublishedRootAlbumsButNotUnpublishedOnes(): void
    {
        $this->authenticate(
            8030,
            UserRoles::Member,
        );

        // The Gala and Trip albums are both dated six and three months ago, so they share an association year.
        $names = $this->albumNamesForYearOf($this->albumRepository()->findOneBy(['name' => 'Gala 2024']));

        self::assertContains(
            'Gala 2024',
            $names,
        );
        self::assertContains(
            'Trip 2024',
            $names,
        );
        self::assertNotContains(
            'Draft Album',
            $names,
            'A member must not see an unpublished album.',
        );
    }

    public function testBoardDoesNotSeeUnpublishedRootAlbumsInBrowsing(): void
    {
        $this->authenticate(
            8025,
            UserRoles::Board,
        );

        // Drafts are admin-only, so the public overview omits them even for the board (to avoid confusion).
        $names = $this->albumNamesForYearOf($this->albumRepository()->findOneBy(['published' => false]));

        self::assertNotContains(
            'Draft Album',
            $names,
        );
    }

    public function testYearsRunFromNewestToOldestAlbum(): void
    {
        $this->authenticate(
            8030,
            UserRoles::Member,
        );

        $years = $this->service()->getViewableRootAlbumYears();

        self::assertNotEmpty($years);
        // The list is a descending, contiguous range, so each entry is strictly smaller than the one before it.
        $previous = null;
        foreach ($years as $year) {
            if (null !== $previous) {
                self::assertLessThan(
                    $previous,
                    $year,
                );
            }

            $previous = $year;
        }
    }

    public function testFindViewableAlbumRejectsAnUnpublishedAlbumForAMember(): void
    {
        $this->authenticate(
            8030,
            UserRoles::Member,
        );
        $draft = $this->albumRepository()->findOneBy(['published' => false]);
        self::assertInstanceOf(
            Album::class,
            $draft,
            'The seed is expected to contain an unpublished album.',
        );

        self::assertNull($this->service()->findViewableAlbum((int) $draft->getId()));
    }

    public function testBoardCannotFindAnUnpublishedAlbumThroughBrowsing(): void
    {
        $this->authenticate(
            8025,
            UserRoles::Board,
        );
        $draft = $this->albumRepository()->findOneBy(['published' => false]);
        self::assertInstanceOf(
            Album::class,
            $draft,
            'The seed is expected to contain an unpublished album.',
        );

        // The public album page 404s on a draft even for the board; it is reached via the admin section instead.
        self::assertNull($this->service()->findViewableAlbum((int) $draft->getId()));
    }

    public function testSearchNarrowsTheAlbumsToNameMatches(): void
    {
        $this->authenticate(
            8030,
            UserRoles::Member,
        );
        $gala = $this->albumRepository()->findOneBy(['name' => 'Gala 2024']);
        self::assertInstanceOf(
            Album::class,
            $gala,
        );
        $start = $gala->getStartDateTime();
        self::assertInstanceOf(
            DateTime::class,
            $start,
        );

        $byMonth = $this->service()->getViewableRootAlbumsByMonth(
            AssociationYear::fromDate($start)->getYear(),
            'Trip',
        );
        $names = array_map(
            static fn (Album $album): string => $album->getName(),
            array_merge(...array_values($byMonth)),
        );

        self::assertContains(
            'Trip 2024',
            $names,
        );
        self::assertNotContains(
            'Gala 2024',
            $names,
        );
    }

    public function testViewableChildrenReturnsThePublishedSubAlbums(): void
    {
        $this->authenticate(
            8030,
            UserRoles::Member,
        );
        $gala = $this->albumRepository()->findOneBy(['name' => 'Gala 2024']);
        self::assertInstanceOf(
            Album::class,
            $gala,
            'The seed is expected to contain the Gala album.',
        );

        self::assertCount(
            2,
            $this->service()->getViewableChildren($gala),
        );
    }

    /**
     * The names of the viewable root albums that fall in the same association year as the given album.
     *
     * @return string[]
     */
    private function albumNamesForYearOf(?Album $album): array
    {
        self::assertInstanceOf(
            Album::class,
            $album,
            'The seed is expected to contain the album.',
        );
        $start = $album->getStartDateTime();
        self::assertInstanceOf(
            DateTime::class,
            $start,
        );

        $byMonth = $this->service()->getViewableRootAlbumsByMonth(AssociationYear::fromDate($start)->getYear());

        return array_map(
            static fn (Album $album): string => $album->getName(),
            array_merge(...array_values($byMonth)),
        );
    }

    private function service(): AlbumService
    {
        return self::getContainer()->get(AlbumService::class);
    }

    private function albumRepository(): AlbumRepository
    {
        return self::getContainer()->get(AlbumRepository::class);
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
