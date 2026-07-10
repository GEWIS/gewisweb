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
     * Move a photo to another album. Both albums' photo sets change, so both covers are queued for regeneration.
     */
    public function movePhoto(
        Photo $photo,
        Album $destination,
    ): void {
        $source = $photo->getAlbum();
        if ($source->getId() === $destination->getId()) {
            return;
        }

        $photo->setAlbum($destination);
        $this->entityManager->flush();

        $this->messageBus->dispatch(new GenerateAlbumCoverMessage((int) $source->getId()));
        $this->messageBus->dispatch(new GenerateAlbumCoverMessage((int) $destination->getId()));
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
