<?php

declare(strict_types=1);

namespace App\Service\Photo;

use App\Entity\Photo\Album;
use App\Entity\Photo\Photo;
use App\Message\Photo\GenerateAlbumCoverMessage;
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
     * Move photos to another album in one go: reassign them, flush once, then regenerate the cover of each album whose
     * photo set actually changed (the destination and every distinct source), each cover at most once.
     *
     * @param Photo[] $photos
     */
    public function movePhotos(
        array $photos,
        Album $destination,
    ): void {
        $affectedAlbumIds = [];
        foreach ($photos as $photo) {
            $source = $photo->getAlbum();
            if ($source->getId() === $destination->getId()) {
                continue;
            }

            $affectedAlbumIds[(int) $source->getId()] = true;
            $photo->setAlbum($destination);
        }

        if ([] === $affectedAlbumIds) {
            return;
        }

        $this->entityManager->flush();

        $affectedAlbumIds[(int) $destination->getId()] = true;
        foreach (array_keys($affectedAlbumIds) as $albumId) {
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
        $albumIds = [];
        foreach ($photos as $photo) {
            $paths[] = $photo->getPath();
            $albumIds[(int) $photo->getAlbum()->getId()] = true;
            $this->entityManager->remove($photo);
        }

        $this->entityManager->flush();

        foreach (array_unique($paths) as $path) {
            $this->fileStorage->remove($path);
        }

        foreach (array_keys($albumIds) as $albumId) {
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
