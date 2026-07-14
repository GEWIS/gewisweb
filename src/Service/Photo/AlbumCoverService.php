<?php

declare(strict_types=1);

namespace App\Service\Photo;

use App\Entity\Application\Enums\StorageNamespace;
use App\Entity\Photo\Album;
use App\Repository\Photo\PhotoRepository;
use App\Service\Application\FileStorage;
use App\Service\Application\ImageManagerProvider;
use Intervention\Image\Encoders\WebpEncoder;

use function count;
use function hash;
use function sprintf;

/**
 * Composes an album's 2x2 cover mosaic (legacy behaviour) as a single 1280x720 WebP master, from which the cover
 * variants are derived. Each of the four tiles is cover-cropped to 640x360 before compositing, so no full-resolution
 * frame is ever held in the canvas. This, combined with running in a Messenger worker (off the request thread) and the
 * result being cached immutably, replaces the legacy synchronous per-request cover render.
 */
final readonly class AlbumCoverService
{
    private const int MASTER_WIDTH = 1280;
    private const int MASTER_HEIGHT = 720;
    private const int TILE_WIDTH = 640;
    private const int TILE_HEIGHT = 360;
    private const int QUALITY = 85;

    public function __construct(
        private ImageManagerProvider $imageManagerProvider,
        private FileStorage $fileStorage,
        private PhotoRepository $photoRepository,
    ) {
    }

    /**
     * Compose and store the album's cover mosaic and point the album at it. Returns the stored cover path, or null when
     * the album (and its sub-albums) hold no photos to build a cover from. The caller flushes.
     */
    public function generateForAlbum(Album $album): ?string
    {
        $photos = $this->photoRepository->getRandomPhotosFromAlbums(
            $this->albumTree($album),
            4,
        );

        if ([] === $photos) {
            return null;
        }

        $manager = $this->imageManagerProvider->create();
        $canvas = $manager->createImage(
            self::MASTER_WIDTH,
            self::MASTER_HEIGHT,
        );

        // Fill the four quadrants, cycling through the available photos when the album has fewer than four.
        $quadrants = [
            [
                0,
                0,
            ],
            [
                self::TILE_WIDTH,
                0,
            ],
            [
                0,
                self::TILE_HEIGHT,
            ],
            [
                self::TILE_WIDTH,
                self::TILE_HEIGHT,
            ],
        ];
        foreach ($quadrants as $index => [$x, $y]) {
            $photo = $photos[$index % count($photos)];
            $tile = $manager->decodeBinary($this->fileStorage->read($photo->getPath()))
                ->cover(
                    self::TILE_WIDTH,
                    self::TILE_HEIGHT,
                );
            $canvas->insert(
                $tile,
                $x,
                $y,
            );
        }

        $bytes = $canvas->encode(new WebpEncoder(quality: self::QUALITY, strip: true))->toString();

        // Content-address the mosaic so an unchanged album keeps the same cover path (and de-duplicates).
        $path = sprintf(
            '%s/%s.webp',
            StorageNamespace::PhotoCover->directory(),
            hash(
                'sha256',
                $bytes,
            ),
        );
        $this->fileStorage->write(
            $path,
            $bytes,
        );
        $album->setCoverPath($path);

        return $path;
    }

    /**
     * The album and all of its descendant albums, so a cover can draw on photos from sub-albums too.
     *
     * @return list<Album>
     */
    private function albumTree(Album $album): array
    {
        $albums = [$album];

        foreach ($album->getChildren() as $child) {
            foreach ($this->albumTree($child) as $descendant) {
                $albums[] = $descendant;
            }
        }

        return $albums;
    }
}
