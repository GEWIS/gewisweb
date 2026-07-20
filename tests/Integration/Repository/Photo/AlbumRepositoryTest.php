<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository\Photo;

use App\Entity\Photo\Album;
use App\Repository\Photo\AlbumRepository;
use App\Tests\Integration\DatabaseTestCase;

use function array_map;

/**
 * The public cross-year album search: a name LIKE across every association year, restricted to published, dated, root
 * albums (the graduate rule runs later, per result, in the service). Pins the query against the seed.
 */
final class AlbumRepositoryTest extends DatabaseTestCase
{
    public function testSearchPublishedAlbumsMatchesNameAcrossYears(): void
    {
        $results = $this->repository()->searchPublishedAlbums('Trip');

        self::assertContains(
            'Trip 2024',
            array_map(
                static fn (Album $album): string => $album->getName(),
                $results,
            ),
        );

        // Every result is a published, dated, root album; the voter/graduate rule is applied later, in the service.
        foreach ($results as $album) {
            self::assertNull($album->getParent());
            self::assertNotNull($album->getStartDateTime());
        }
    }

    public function testSearchPublishedAlbumsExcludesDrafts(): void
    {
        $draft = $this->repository()->findOneBy(['published' => false]);
        self::assertInstanceOf(
            Album::class,
            $draft,
            'The seed is expected to contain an unpublished album.',
        );

        $ids = array_map(
            static fn (Album $album): int => (int) $album->getId(),
            $this->repository()->searchPublishedAlbums($draft->getName()),
        );

        self::assertNotContains(
            (int) $draft->getId(),
            $ids,
        );
    }

    private function repository(): AlbumRepository
    {
        return self::getContainer()->get(AlbumRepository::class);
    }
}
