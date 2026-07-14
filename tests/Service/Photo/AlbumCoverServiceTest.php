<?php

declare(strict_types=1);

namespace App\Tests\Service\Photo;

use App\Entity\Application\Enums\StorageNamespace;
use App\Entity\Photo\Album;
use App\Entity\Photo\Photo;
use App\Repository\Photo\PhotoRepository;
use App\Service\Application\FileStorage;
use App\Service\Application\ImageManagerProvider;
use App\Service\Photo\AlbumCoverService;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

use function dirname;
use function getimagesizefromstring;
use function str_ends_with;
use function str_starts_with;

use const IMAGETYPE_WEBP;

/**
 * The album cover mosaic must always be a landscape 1280x720 WebP regardless of the source orientation (cards are
 * landscape), and, being content-addressed, must be stable for an unchanged photo set. Verified on the GD driver with
 * a deliberately portrait source mixed with a landscape one.
 */
final class AlbumCoverServiceTest extends TestCase
{
    public function testComposesALandscapeMosaicFromMixedOrientationSources(): void
    {
        $storage = new FileStorage(new Filesystem(new InMemoryFilesystemAdapter()));
        $service = $this->service(
            $storage,
            [
                $this->storedPhoto(
                    $storage,
                    'gala-dinner-2.jpg',
                ),   // 600x800 portrait
                $this->storedPhoto(
                    $storage,
                    'gala-dinner-1.jpg',
                ),   // 800x600 landscape
            ],
        );
        $album = $this->albumWithId(7);

        $coverPath = $service->generateForAlbum($album);

        self::assertNotNull($coverPath);
        self::assertSame(
            $coverPath,
            $album->getCoverPath(),
        );
        // The cover is scoped under the album it belongs to.
        self::assertTrue(str_starts_with($coverPath, 'photos/covers/7/'));
        self::assertTrue(str_ends_with($coverPath, '.webp'));

        $info = getimagesizefromstring($storage->read($coverPath));
        self::assertIsArray($info);
        self::assertSame(
            1280,
            $info[0],
        );
        self::assertSame(
            720,
            $info[1],
        );
        self::assertSame(
            IMAGETYPE_WEBP,
            $info[2],
        );
    }

    public function testReturnsNullWhenTheAlbumHasNoPhotos(): void
    {
        $storage = new FileStorage(new Filesystem(new InMemoryFilesystemAdapter()));
        $service = $this->service(
            $storage,
            [],
        );

        self::assertNull($service->generateForAlbum(new Album()));
    }

    public function testCoverPathIsStableForTheSamePhotoSet(): void
    {
        $storage = new FileStorage(new Filesystem(new InMemoryFilesystemAdapter()));
        $photos = [
            $this->storedPhoto(
                $storage,
                'trip-1.jpg',
            ),
        ];

        // The same album (same id) with the same photos must resolve to the same scoped cover path.
        $album = $this->albumWithId(5);
        $first = $this->service($storage, $photos)->generateForAlbum($album);
        $second = $this->service($storage, $photos)->generateForAlbum($album);

        self::assertSame(
            $first,
            $second,
        );
    }

    public function testComposesAPortraitColumnMosaicFromPortraitSources(): void
    {
        $storage = new FileStorage(new Filesystem(new InMemoryFilesystemAdapter()));
        // Three portrait sources make the album portrait-majority, so the column layout is used; the master stays the
        // same 1280x720 landscape frame (only the tiling within it differs).
        $service = $this->service(
            $storage,
            [
                $this->storedPhoto(
                    $storage,
                    'gala-dinner-2.jpg',
                ),
                $this->storedPhoto(
                    $storage,
                    'gala-dinner-2.jpg',
                ),
                $this->storedPhoto(
                    $storage,
                    'gala-dinner-2.jpg',
                ),
            ],
        );

        $coverPath = $service->generateForAlbum($this->albumWithId(9));

        self::assertNotNull($coverPath);
        $info = getimagesizefromstring($storage->read($coverPath));
        self::assertIsArray($info);
        self::assertSame(
            1280,
            $info[0],
        );
        self::assertSame(
            720,
            $info[1],
        );
        self::assertSame(
            IMAGETYPE_WEBP,
            $info[2],
        );
    }

    /**
     * An album with a database id set, so its cover can be stored under the album-scoped namespace without persisting.
     */
    private function albumWithId(int $id): Album
    {
        $album = new Album();
        new ReflectionProperty(
            Album::class,
            'id',
        )->setValue(
            $album,
            $id,
        );

        return $album;
    }

    /**
     * @param list<Photo> $photos
     */
    private function service(
        FileStorage $storage,
        array $photos,
    ): AlbumCoverService {
        $photoRepository = self::createStub(PhotoRepository::class);
        $photoRepository->method('getRandomPhotosFromAlbums')->willReturn($photos);

        return new AlbumCoverService(
            new ImageManagerProvider(),
            $storage,
            $photoRepository,
        );
    }

    private function storedPhoto(
        FileStorage $storage,
        string $fixture,
    ): Photo {
        $path = $storage->store(
            StorageNamespace::PhotoOriginal,
            dirname(
                __DIR__,
                3,
            ) . '/tests/Resources/images/' . $fixture,
            '1',
        )->path;

        $photo = new Photo();
        $photo->setPath($path);

        return $photo;
    }
}
