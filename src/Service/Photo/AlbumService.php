<?php

declare(strict_types=1);

namespace App\Service\Photo;

use App\Entity\Decision\AssociationYear;
use App\Entity\Photo\Album;
use App\Repository\Photo\AlbumRepository;
use App\Repository\Photo\PhotoRepository;
use App\Security\Photo\AlbumVoter;
use Symfony\Bundle\SecurityBundle\Security;

use function krsort;
use function stripos;

/**
 * Read access to photo albums for the public browsing pages. Every album that leaves this service has passed the
 * {@see AlbumVoter}, so callers do not repeat the published/graduate checks.
 */
final readonly class AlbumService
{
    public function __construct(
        private AlbumRepository $albumRepository,
        private PhotoRepository $photoRepository,
        private Security $security,
    ) {
    }

    /**
     * The sub-album and direct-photo counts an overview's album cards need, each gathered in one query, so a grid does
     * not issue a `COUNT` per card. Both maps are keyed by album id; a missing key means zero.
     *
     * @param Album[] $albums
     *
     * @return array{subAlbums: array<int, int>, photos: array<int, int>}
     */
    public function getCardCounts(array $albums): array
    {
        return [
            'subAlbums' => $this->albumRepository->getSubAlbumCounts($albums),
            'photos' => $this->photoRepository->getDirectPhotoCounts($albums),
        ];
    }

    /**
     * The association years (as their first year, e.g. 2024 for '2024-2025') that hold viewable root albums, most
     * recent first. Used to populate the year filter on the overview; the range runs from the newest to the oldest
     * dated album.
     *
     * @return list<int>
     */
    public function getViewableRootAlbumYears(): array
    {
        // Public browsing is published-only for everyone (drafts live in the admin), so the year range is too.
        $newest = $this->albumRepository->getNewestAlbum()?->getStartDateTime();
        $oldest = $this->albumRepository->getOldestAlbum()?->getStartDateTime();

        if (
            null === $newest
            || null === $oldest
        ) {
            return [];
        }

        $years = [];
        $oldestYear = AssociationYear::fromDate($oldest)->getYear();
        for ($year = AssociationYear::fromDate($newest)->getYear(); $year >= $oldestYear; --$year) {
            $years[] = $year;
        }

        return $years;
    }

    /**
     * The viewable root albums of one association year, grouped by month (keyed 'Y-m', most recent month first) so the
     * overview can print a month divider before each group. An optional search narrows the albums to those whose name
     * contains it. Only published albums are surfaced (drafts live in the admin, even for the board); graduates only
     * see the albums the voter allows them.
     *
     * @return array<string, Album[]>
     */
    public function getViewableRootAlbumsByMonth(
        int $year,
        ?string $search = null,
    ): array {
        $associationYear = AssociationYear::fromYear($year);
        $albums = $this->albumRepository->getAlbumsInDateRange(
            $associationYear->getStartDate(),
            $associationYear->getEndDate(),
        );

        $grouped = [];
        foreach ($albums as $album) {
            $start = $album->getStartDateTime();
            if (
                null === $start
                || !$this->security->isGranted(
                    AlbumVoter::VIEW,
                    $album,
                )
            ) {
                continue;
            }

            if (
                null !== $search
                && '' !== $search
                && false === stripos(
                    $album->getName(),
                    $search,
                )
            ) {
                continue;
            }

            $grouped[$start->format('Y-m')][] = $album;
        }

        return $grouped;
    }

    /**
     * Every root album grouped by association year (keyed by the year's first number, most recent year first) for the
     * board admin overview. Unlike the public overview this is not split by month and always includes unpublished
     * albums; albums without a start date are gathered under the 0 key, which sorts last.
     *
     * @return array<int, Album[]>
     */
    public function getRootAlbumsByYear(): array
    {
        $grouped = [];
        foreach ($this->albumRepository->findRootAlbums() as $album) {
            $start = $album->getStartDateTime();
            $year = null === $start
                ? 0
                : AssociationYear::fromDate($start)->getYear();

            $grouped[$year][] = $album;
        }

        krsort($grouped);

        return $grouped;
    }

    /**
     * The album with this id if it exists and the current user may view it, otherwise null (the controller turns that
     * into a 404, so a hidden album's existence is not leaked).
     */
    public function findViewableAlbum(int $id): ?Album
    {
        $album = $this->albumRepository->find($id);
        if (
            null === $album
            || !$this->security->isGranted(
                AlbumVoter::VIEW,
                $album,
            )
        ) {
            return null;
        }

        return $album;
    }

    /**
     * The sub-albums of an album that the current user may view. Each is checked individually because a graduate may be
     * allowed into some sub-albums but not their siblings.
     *
     * @return Album[]
     */
    public function getViewableChildren(Album $album): array
    {
        $children = [];
        foreach ($album->getChildren() as $child) {
            if (
                !$this->security->isGranted(
                    AlbumVoter::VIEW,
                    $child,
                )
            ) {
                continue;
            }

            $children[] = $child;
        }

        return $children;
    }
}
