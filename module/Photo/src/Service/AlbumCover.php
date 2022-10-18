<?php

namespace Photo\Service;

use Application\Service\FileStorage as FileStorageService;
use Imagick;
use Photo\Mapper\{
    Album as AlbumMapper,
    Photo as PhotoMapper,
};
use Photo\Model\Album as AlbumModel;

/**
 * Album cover services. Used for (re)generating album covers.
 */
class AlbumCover
{
    public function __construct(
        private readonly PhotoMapper $photoMapper,
        private readonly AlbumMapper $albumMapper,
        private readonly FileStorageService $storage,
        private readonly array $photoConfig,
        private readonly array $storageConfig,
    ) {
    }

    /**
     * Creates, stores and returns the path to a cover image, a mozaic generated from
     * a random selection of photos in the album or sub-albums.
     *
     * @param AlbumModel $album the album to create the cover for
     *
     * @return string|null the path to the cover image
     */
    public function createCover(AlbumModel $album): ?string
    {
        $cover = $this->generateCover($album);

        if (null === $cover) {
            return null;
        }

        $tempFileName = sys_get_temp_dir() . '/CoverImage' . random_int(0, getrandmax()) . '.png';
        $cover->writeImage($tempFileName);

        return $this->storage->storeFile($tempFileName, false);
    }

    /**
     * Creates a cover image for the given album.
     *
     * @param AlbumModel $album the album to create a cover image for
     *
     * @return Imagick|null the cover image or null if one could not be created
     */
    protected function generateCover(AlbumModel $album): ?Imagick
    {
        $columns = $this->photoConfig['album_cover']['cols'];
        $rows = $this->photoConfig['album_cover']['rows'];
        $count = $columns * $rows;
        $images = $this->getImages($album, $count);
        /*
         * If there are not enough images available to fill the matrix we
         * reduce the amount of rows and columns
         */
        while (count($images) < $count) {
            if ($columns < $rows) {
                --$rows;
            } else {
                --$columns;
            }
            $count = $rows * $columns;
        }
        // Make a blank canvas
        $target = new Imagick();
        $target->newImage(
            $this->photoConfig['album_cover']['width'],
            $this->photoConfig['album_cover']['height'],
            $this->photoConfig['album_cover']['background']
        );

        if (0 === count($images)) {
            return null;
        }

        $this->drawComposition($target, $columns, $rows, $images);
        $target->setImageFormat('png');

        return $target;
    }

    /**
     * Returns the images needed to fill the album cover.
     *
     * @param AlbumModel $album
     * @param int $count the amount of images needed
     *
     * @return array of Imagick - a list of the images
     */
    protected function getImages(
        AlbumModel $album,
        int $count,
    ): array {
        $photos = $this->photoMapper->getRandomAlbumPhotos($album, $count);
        //retrieve more photo's from subalbums
        foreach ($this->albumMapper->getSubAlbums($album) as $subAlbum) {
            $needed = $count - count($photos);
            $photos = array_merge($photos, $this->photoMapper->getRandomAlbumPhotos($subAlbum, $needed));
        }
        //convert the photo objects to Imagick objects
        $images = [];
        foreach ($photos as $photo) {
            $imagePath = $this->storageConfig['storage_dir'] . '/' . $photo->getSmallThumbPath();
            $images[] = new Imagick($imagePath);
        }

        return $images;
    }

    /**
     * Draws the mosaic of photos.
     *
     * @param Imagick $target the target object to draw to
     * @param int $columns The amount of columns to fill
     * @param int $rows The amount of rows to fill
     * @param array $images of Imagick the list of images to fill the mosaic with
     */
    protected function drawComposition(
        Imagick $target,
        int $columns,
        int $rows,
        array $images,
    ): void {
        $innerBorder = $this->photoConfig['album_cover']['inner_border'];
        $outerBorder = $this->photoConfig['album_cover']['inner_border'];

        //calculate the total size of all images inside the outer border
        $innerWidth = $this->photoConfig['album_cover']['width'] - 2 * $outerBorder;
        $innerHeight = $this->photoConfig['album_cover']['height'] - 2 * $outerBorder;

        $innerBorderWidth = ($columns - 1) * $innerBorder;
        $innerBorderHeight = ($rows - 1) * $innerBorder;
        //calculate required size of images based on inner border
        $imageWidth = floor(($innerWidth - $innerBorderWidth) / $columns);
        $imageHeight = floor(($innerHeight - $innerBorderHeight) / $rows);
        //increase outer border due to flooring of image dimensions
        $realInnerWidth = ($columns * $imageWidth + $innerBorderWidth);
        $realInnerHeight = ($rows * $imageHeight + $innerBorderHeight);
        $outerBorderX = $outerBorder + ceil(($innerWidth - $realInnerWidth) / 2);
        $outerBorderY = $outerBorder + ceil(($innerHeight - $realInnerHeight) / 2);

        //compose all images
        for ($x = 0; $x < $columns; ++$x) {
            for ($y = 0; $y < $rows; ++$y) {
                $image = $this->resizeCropImage(
                    $images[$x * $rows + $y],
                    (int) $imageWidth,
                    (int) $imageHeight
                );
                $target->compositeImage(
                    $image,
                    Imagick::COMPOSITE_COPY,
                    (int) (($imageWidth + $innerBorder) * $x + $outerBorderX),
                    (int) (($imageHeight + $innerBorder) * $y + $outerBorderY)
                );
            }
        }
    }

    /**
     * Specialized function to resize and crop photos such that they always
     * fill the full width and height without damaging the aspect ratio of the
     * photo.
     *
     * @param Imagick $image The Imagick object to be resized and cropped
     * @param int $width The desired width
     * @param int $height The desired height
     *
     * @return Imagick $image
     */
    protected function resizeCropImage(
        Imagick $image,
        int $width,
        int $height,
    ): Imagick {
        $imageHeight = $image->getImageGeometry()['height'];
        $imageWidth = $image->getImageGeometry()['width'];
        $resizeWidth = max($width, floor($imageWidth * $height / $imageHeight));
        $resizeHeight = max($height, floor($imageHeight * $width / $imageWidth));
        $image->resizeImage($resizeWidth, $resizeHeight, Imagick::FILTER_LANCZOS, 1);
        $cropX = 0;
        if ($width < $resizeWidth) {
            $cropX = floor(($resizeWidth - $width) / 2);
        }
        $image->cropImage($width, $height, $cropX, 0);

        return $image;
    }
}
