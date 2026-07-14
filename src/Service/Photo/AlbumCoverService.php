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
use function getimagesizefromstring;
use function hash;
use function sprintf;

/**
 * Composes an album's cover mosaic as a single 1280x720 WebP master, from which the cover variants are derived. The
 * four tiles are laid out to suit the photos' orientation: landscape-heavy albums keep the legacy 2x2 grid of 640x360
 * tiles, while portrait-heavy albums use four 320x720 columns (a landscape cell would crop a portrait to a thin
 * horizontal slice). Each tile is cover-cropped before compositing, so no full-resolution frame is ever held in the
 * canvas. This, combined with running in a Messenger worker (off the request thread) and the result being cached
 * immutably, replaces the legacy synchronous per-request cover render.
 */
final readonly class AlbumCoverService
{
    private const int MASTER_WIDTH = 1280;
    private const int MASTER_HEIGHT = 720;
    private const int TILE_WIDTH = 640;
    private const int TILE_HEIGHT = 360;
    private const int COLUMN_WIDTH = 320;
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

        // Read each sampled photo once and note its orientation (from the header alone) to choose the tiling.
        $data = [];
        $portrait = 0;
        foreach ($photos as $photo) {
            $bytes = $this->fileStorage->read($photo->getPath());
            $data[] = $bytes;

            $size = getimagesizefromstring($bytes);
            if (
                false === $size
                || $size[1] <= $size[0]
            ) {
                continue;
            }

            ++$portrait;
        }

        // A landscape cell crops a portrait to a thin slice, so lay a portrait-majority album out in columns instead.
        $cells = $portrait * 2 > count($data)
            ? $this->columnCells()
            : $this->gridCells();

        $manager = $this->imageManagerProvider->create();
        $canvas = $manager->createImage(
            self::MASTER_WIDTH,
            self::MASTER_HEIGHT,
        );

        // Fill each cell, cycling through the available photos when the album has fewer than four.
        foreach ($cells as $index => [$x, $y, $width, $height]) {
            $tile = $manager->decodeBinary($data[$index % count($data)])
                ->cover(
                    $width,
                    $height,
                );
            $canvas->insert(
                $tile,
                $x,
                $y,
            );
        }

        $bytes = $canvas->encode(new WebpEncoder(quality: self::QUALITY, strip: true))->toString();

        // Content-address the mosaic within the album's own scope, so an unchanged album keeps the same cover path.
        $path = sprintf(
            '%s/%s.webp',
            StorageNamespace::PhotoCover->directory((string) $album->getId()),
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
     * The 2x2 grid of 640x360 landscape tiles, for a landscape-heavy album.
     *
     * @return list<array{int, int, int, int}> each cell as [x, y, width, height]
     */
    private function gridCells(): array
    {
        return [
            [
                0,
                0,
                self::TILE_WIDTH,
                self::TILE_HEIGHT,
            ],
            [
                self::TILE_WIDTH,
                0,
                self::TILE_WIDTH,
                self::TILE_HEIGHT,
            ],
            [
                0,
                self::TILE_HEIGHT,
                self::TILE_WIDTH,
                self::TILE_HEIGHT,
            ],
            [
                self::TILE_WIDTH,
                self::TILE_HEIGHT,
                self::TILE_WIDTH,
                self::TILE_HEIGHT,
            ],
        ];
    }

    /**
     * Four full-height 320x720 columns, for a portrait-heavy album.
     *
     * @return list<array{int, int, int, int}> each cell as [x, y, width, height]
     */
    private function columnCells(): array
    {
        return [
            [
                0,
                0,
                self::COLUMN_WIDTH,
                self::MASTER_HEIGHT,
            ],
            [
                self::COLUMN_WIDTH,
                0,
                self::COLUMN_WIDTH,
                self::MASTER_HEIGHT,
            ],
            [
                self::COLUMN_WIDTH * 2,
                0,
                self::COLUMN_WIDTH,
                self::MASTER_HEIGHT,
            ],
            [
                self::COLUMN_WIDTH * 3,
                0,
                self::COLUMN_WIDTH,
                self::MASTER_HEIGHT,
            ],
        ];
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
