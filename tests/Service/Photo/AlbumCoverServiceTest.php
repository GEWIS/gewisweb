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
        $album = new Album();

        $coverPath = $service->generateForAlbum($album);

        self::assertNotNull($coverPath);
        self::assertSame(
            $coverPath,
            $album->getCoverPath(),
        );
        self::assertTrue(str_starts_with($coverPath, 'photos/covers/'));
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

        $first = $this->service($storage, $photos)->generateForAlbum(new Album());
        $second = $this->service($storage, $photos)->generateForAlbum(new Album());

        self::assertSame(
            $first,
            $second,
        );
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
            ) . '/src/DataFixtures/Photo/resources/' . $fixture,
        )->path;

        $photo = new Photo();
        $photo->setPath($path);

        return $photo;
    }
}
