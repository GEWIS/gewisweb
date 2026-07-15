<?php

declare(strict_types=1);

namespace App\Service\Photo;

use App\Entity\Photo\Album;
use App\Entity\Photo\Photo;
use App\Message\Photo\GenerateAlbumCoverMessage;
use App\Repository\Photo\PhotoRepository;
use App\Service\Application\FileStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

use function array_keys;
use function array_unique;

/**
 * Administrative album operations: moving a photo to another album, and deleting an album with its whole subtree.
 * Doctrine cascades the object graph on remove (sub-albums, photos, tags, votes, profile photos, weekly photo), so the
 * service only has to reclaim the stored files afterwards and keep the album covers in step by re-generating them when
 * a photo set changes. Originals are content-addressed but scoped per album, so a file is never shared between albums
 * and reclaiming it is unconditional.
 */
final readonly class AlbumAdminService
{
    public function __construct(
        private FileStorage $fileStorage,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
        private PhotoRepository $photoRepository,
    ) {
    }

    /**
     * Queue a regeneration of the album's cover mosaic, e.g. after the board has changed which photos it should show.
     */
    public function regenerateCover(Album $album): void
    {
        $this->messageBus->dispatch(new GenerateAlbumCoverMessage((int) $album->getId()));
    }

    /**
     * Set the album's date range to span its photos' capture times, e.g. after photos have been uploaded, moved or
     * deleted. An album with no photos loses its range.
     */
    public function updateDateRange(Album $album): void
    {
        $this->applyDateRange($album);
        $this->entityManager->flush();
    }

    /**
     * Move photos to another album in one go: reassign them, flush once, then regenerate the cover of each album whose
     * photo set actually changed (the destination and every distinct source), each cover at most once.
     *
     * @param Photo[] $photos
     */
    public function movePhotos(
        array $photos,
        Album $destination,
    ): void {
        $affectedAlbums = [];
        foreach ($photos as $photo) {
            $source = $photo->getAlbum();
            if ($source->getId() === $destination->getId()) {
                continue;
            }

            $affectedAlbums[(int) $source->getId()] = $source;
            $photo->setAlbum($destination);
        }

        if ([] === $affectedAlbums) {
            return;
        }

        $this->entityManager->flush();

        $affectedAlbums[(int) $destination->getId()] = $destination;
        foreach ($affectedAlbums as $album) {
            $this->applyDateRange($album);
        }

        $this->entityManager->flush();

        foreach (array_keys($affectedAlbums) as $albumId) {
            $this->messageBus->dispatch(new GenerateAlbumCoverMessage($albumId));
        }
    }

    /**
     * Delete a set of photos, reclaiming their files and re-covering each album they came from.
     *
     * @param Photo[] $photos
     */
    public function deletePhotos(array $photos): void
    {
        $paths = [];
        $albums = [];
        foreach ($photos as $photo) {
            $paths[] = $photo->getPath();
            $albums[(int) $photo->getAlbum()->getId()] = $photo->getAlbum();
            $this->entityManager->remove($photo);
        }

        $this->entityManager->flush();

        foreach (array_unique($paths) as $path) {
            $this->fileStorage->remove($path);
        }

        foreach ($albums as $album) {
            $this->applyDateRange($album);
        }

        $this->entityManager->flush();

        foreach (array_keys($albums) as $albumId) {
            $this->messageBus->dispatch(new GenerateAlbumCoverMessage($albumId));
        }
    }

    public function deleteAlbum(Album $album): void
    {
        // Collect every stored path in the subtree up front; the strings outlive the entities the delete tears down.
        $paths = [];
        $coverPaths = [];
        $this->collectPaths(
            $album,
            $paths,
            $coverPaths,
        );

        // One transaction: removing the aggregate root cascades to the entire subtree.
        $this->entityManager->wrapInTransaction(function () use ($album): void {
            $this->entityManager->remove($album);
            $this->entityManager->flush();
        });

        // Reclaim the bytes only after the rows are gone and committed; photos are scoped per album, so nothing is
        // shared with another album.
        foreach (array_unique($paths) as $path) {
            $this->fileStorage->remove($path);
        }

        foreach (array_unique($coverPaths) as $coverPath) {
            $this->fileStorage->remove($coverPath);
        }
    }

    private function applyDateRange(Album $album): void
    {
        $range = $this->photoRepository->getDateRange($album);
        $album->setStartDateTime($range[0]);
        $album->setEndDateTime($range[1]);
    }

    /**
     * @param list<string> $paths
     * @param list<string> $coverPaths
     */
    private function collectPaths(
        Album $album,
        array &$paths,
        array &$coverPaths,
    ): void {
        foreach ($album->getChildren() as $child) {
            $this->collectPaths(
                $child,
                $paths,
                $coverPaths,
            );
        }

        foreach ($album->getPhotos() as $photo) {
            $paths[] = $photo->getPath();
        }

        $coverPath = $album->getCoverPath();
        if (null === $coverPath) {
            return;
        }

        $coverPaths[] = $coverPath;
    }
}
