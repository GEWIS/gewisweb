<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Photo;

use App\Entity\Application\Enums\StorageNamespace;
use App\Entity\Photo\Album;
use App\Entity\Photo\Photo;
use App\Message\Photo\GenerateAlbumCoverMessage;
use App\Repository\Photo\AlbumRepository;
use App\Repository\Photo\PhotoRepository;
use App\Service\Application\FileStorage;
use App\Service\Photo\AlbumAdminService;
use App\Tests\Integration\DatabaseTestCase;
use DateTime;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

use function imagecolorallocate;
use function imagecreatetruecolor;
use function imagefilledrectangle;
use function imagejpeg;
use function is_a;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * The administrative album operations move a photo between albums (re-covering both), delete photos (reclaiming their
 * files, since originals are scoped per album and never shared), and delete an album with its whole subtree. The writes
 * run against the seeded MariaDB and are rolled back by dama; storage is the in-memory adapter, so a reclaimed file can
 * be asserted gone.
 */
final class AlbumAdminServiceTest extends DatabaseTestCase
{
    private int $colour = 0x20;

    public function testMovePhotosReassignsTheAlbumAndQueuesEachCoverOnce(): void
    {
        $trip = $this->album('Trip 2024');
        $gala = $this->album('Gala 2024');
        $photoA = $this->storedPhoto($trip);
        $photoB = $this->storedPhoto($trip);

        $this->service()->movePhotos(
            [
                $photoA,
                $photoB,
            ],
            $gala,
        );

        self::assertSame(
            $gala->getId(),
            $photoA->getAlbum()->getId(),
        );
        self::assertSame(
            $gala->getId(),
            $photoB->getAlbum()->getId(),
        );
        // One cover per distinct affected album (the single source and the destination), not per photo.
        self::assertSame(
            2,
            $this->sentCount(GenerateAlbumCoverMessage::class),
        );
    }

    public function testMovingToTheSameAlbumIsANoOp(): void
    {
        $trip = $this->album('Trip 2024');
        $photo = $this->storedPhoto($trip);

        $this->service()->movePhotos(
            [$photo],
            $trip,
        );

        self::assertSame(
            $trip->getId(),
            $photo->getAlbum()->getId(),
        );
        self::assertSame(
            0,
            $this->sentCount(GenerateAlbumCoverMessage::class),
        );
    }

    public function testDeletePhotosRemovesRowsAndReclaimsFiles(): void
    {
        $trip = $this->album('Trip 2024');
        $photo = $this->storedPhoto($trip);
        $path = $photo->getPath();
        $id = (int) $photo->getId();
        self::assertTrue($this->storage()->exists($path));

        $this->service()->deletePhotos([$photo]);

        self::assertNull($this->photoRepository()->find($id));
        self::assertFalse($this->storage()->exists($path));
        self::assertSame(
            1,
            $this->sentCount(GenerateAlbumCoverMessage::class),
        );
    }

    public function testDeleteAlbumCascadesTheSubtreeAndReclaimsFiles(): void
    {
        // A throwaway parent -> child tree with a stored photo in the child, so the recursive delete has a subtree.
        $parent = $this->newAlbum('Temp parent');
        $child = $this->newAlbum('Temp child');
        $parent->addAlbum($child);
        $this->entityManager->flush();

        $photo = $this->storedPhoto($child);
        $path = $photo->getPath();
        $parentId = (int) $parent->getId();
        $childId = (int) $child->getId();
        $photoId = (int) $photo->getId();
        self::assertTrue($this->storage()->exists($path));

        // Reload the album exactly as the controller does from a route parameter, so the delete walks fresh
        // (uninitialised) collections rather than the in-memory graph this test just wired up.
        $this->entityManager->clear();
        $parent = $this->albumRepository()->find($parentId);
        self::assertInstanceOf(
            Album::class,
            $parent,
        );

        $this->service()->deleteAlbum($parent);

        self::assertNull($this->albumRepository()->find($parentId));
        self::assertNull($this->albumRepository()->find($childId));
        self::assertNull($this->photoRepository()->find($photoId));
        self::assertFalse($this->storage()->exists($path));
    }

    private function service(): AlbumAdminService
    {
        return self::getContainer()->get(AlbumAdminService::class);
    }

    private function storage(): FileStorage
    {
        return self::getContainer()->get(FileStorage::class);
    }

    private function photoRepository(): PhotoRepository
    {
        return self::getContainer()->get(PhotoRepository::class);
    }

    private function albumRepository(): AlbumRepository
    {
        return self::getContainer()->get(AlbumRepository::class);
    }

    private function album(string $name): Album
    {
        $album = $this->albumRepository()->findOneBy(['name' => $name]);
        self::assertInstanceOf(
            Album::class,
            $album,
            'The seed is expected to contain the album.',
        );

        return $album;
    }

    private function newAlbum(string $name): Album
    {
        $album = new Album();
        $album->setName($name);
        $album->setPublished(false);
        $this->entityManager->persist($album);
        $this->entityManager->flush();

        return $album;
    }

    /**
     * A photo backed by a freshly stored image scoped to the album, so its original is present in the in-memory test
     * storage and can be asserted reclaimed. Each call uses a distinct colour so the content hashes never collide.
     */
    private function storedPhoto(Album $album): Photo
    {
        $file = tempnam(
            sys_get_temp_dir(),
            'gewisweb-admin-test',
        );
        self::assertIsString($file);
        $image = imagecreatetruecolor(
            40,
            30,
        );
        self::assertNotFalse($image);
        $colour = imagecolorallocate(
            $image,
            $this->colour++,
            0x40,
            0x80,
        );
        self::assertNotFalse($colour);
        imagefilledrectangle(
            $image,
            0,
            0,
            40,
            30,
            $colour,
        );
        imagejpeg(
            $image,
            $file,
        );

        $stored = $this->storage()->store(
            StorageNamespace::PhotoOriginal,
            $file,
            (string) $album->getId(),
        );
        unlink($file);

        $photo = new Photo();
        $photo->setAlbum($album);
        $photo->setPath($stored->path);
        $photo->setDateTime(new DateTime());
        $photo->setAspectRatio(30 / 40);
        $this->entityManager->persist($photo);
        $this->entityManager->flush();

        return $photo;
    }

    /**
     * @param class-string $messageClass
     */
    private function sentCount(string $messageClass): int
    {
        $transport = self::getContainer()->get('messenger.transport.images');
        self::assertInstanceOf(
            InMemoryTransport::class,
            $transport,
        );

        $count = 0;
        foreach ($transport->getSent() as $envelope) {
            if (
                !is_a(
                    $envelope->getMessage(),
                    $messageClass,
                )
            ) {
                continue;
            }

            ++$count;
        }

        return $count;
    }
}
