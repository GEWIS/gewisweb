<?php

namespace Photo\Service;

use Application\Service\AbstractService;

use Imagick;

/**
 * Album cover services. Used for (re)generating album covers.
 *
 */
class AlbumCover extends AbstractService
{
    /**
     * Creates and returns the path to a cover image, a mozaic generated from
     * a random selection of photos in the album or subalbums.
     *
     * @param Photo\Model\Album $album The album to create the cover for.
     * @return string The path to the cover image.
     */
    public function createCover($album)
    {
        $cover = $this->generateCover($album);
        $tempFileName = sys_get_temp_dir() . '/CoverImage' . rand() . '.png';
        $cover->writeImage($tempFileName);
        $newPath = $this->getPhotoService()->generateStoragePath($tempFileName);
        $config = $this->getConfig();
        rename($tempFileName, $config['upload_dir'] . '/' . $newPath);

        return $newPath;
    }

    /**
     * Creates a cover image for the given album.
     *
     * @param Photo\Model\Album $album The album to create a cover image for.
     * @return Imagick The cover image.
     */
    protected function generateCover($album)
    {
        $config = $this->getConfig();
        $columns = $config['album_cover']['cols'];
        $rows = $config['album_cover']['rows'];
        $count = $columns * $rows;
        $images = $this->getImages($album, $count);
        /*
         * If there are not enough images available to fill the matrix we
         * reduce the amount of rows and columns
         */
        while (count($images) < $count) {
            if ($columns < $rows) {
                $rows--;
            } else {
                $columns--;
            }
            $count = $rows * $columns;
        }
        // Make a blank canvas
        $target = new Imagick();
        $target->newImage(
            $config['album_cover']['width'],
            $config['album_cover']['height'],
            $config['album_cover']['background']
        );

        if (count($images) > 0) {
            $this->drawComposition($target, $columns, $rows, $images);
        }
        $target->setImageFormat("png");

        return $target;
    }

    /**
     * Draws the mosaic of photos.
     *
     * @param Imagick $target The target object to draw to.
     * @param int $columns The amount of columns to fill
     * @param int $rows The amount of rows to fill
     * @param Imagick $images The list of images to fill the mosaic with.
     */
    protected function drawComposition($target, $columns, $rows, $images)
    {
        $config = $this->getConfig();
        $innerBorder = $config['album_cover']['inner_border'];
        $outerBorder = $config['album_cover']['inner_border'];

        //calculate the total size of all images inside the outer border
        $innerWidth = $config['album_cover']['width'] - 2 * $outerBorder;
        $innerHeight = $config['album_cover']['height'] - 2 * $outerBorder;
        //calculate required size of images based on inner border
        $imageWidth = floor(($innerWidth - ($columns - 1) * $innerBorder) / $columns);
        $imageHeight = floor(($innerHeight - ($rows - 1)  * $innerBorder) / $rows);
        //increase outer border due to flooring of image dimensions
        $realInnerWidth = ($columns * $imageWidth + ($columns - 1) * $innerBorder);
        $realInnerHeight = ($rows * $imageHeight  + ($rows - 1) * $innerBorder);
        $outerBorderX = $outerBorder + ceil(($innerWidth - $realInnerWidth) / 2);
        $outerBorderY = $outerBorder + ceil(($innerHeight - $realInnerHeight) / 2);

        //compose all images
        for ($x = 0; $x < $columns; $x++) {
            for ($y = 0; $y < $rows; $y++) {
                $image = $this->resizeCropImage(
                    $images[$x * $rows + $y],
                    $imageWidth,
                    $imageHeight
                );
                $target->compositeImage(
                    $image,
                    imagick::COMPOSITE_COPY,
                    ($imageWidth + $innerBorder) * $x + $outerBorderX,
                    ($imageHeight + $innerBorder) * $y + $outerBorderY
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
     * @return Imagick $image
     */
    protected function resizeCropImage($image, $width, $height)
    {
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

    /**
     * Returns the images needed to fill the album cover
     *
     * @param Photo\Model\Album $album
     * @param int $count the amount of images needed.
     * @return Imagick a list of the images.
     */
    protected function getImages($album, $count)
    {
        $mapper = $this->getAlbumMapper();
        $config = $this->getConfig();
        $photos = $mapper->getRandomAlbumPhotos($album, $count);
        //retrieve more photo's from subalbums
        foreach ($mapper->getSubAlbums($album) as $subAlbum) {
            $needed = $count - count($photos);
            $photos = array_merge($photos, $mapper->getRandomAlbumPhotos($subAlbum, $needed));
        }
        //convert the photo objects to Imagick objects
        $images = array();
        foreach ($photos as $photo) {
            $images[] = new Imagick($config['upload_dir'] . '/' . $photo->getSmallThumbPath());
        }

        return $images;
    }

    /**
     * Get the photo config, as used by this service.
     *
     * @return array
     */
    public function getConfig()
    {
        $config = $this->sm->get('config');

        return $config['photo'];
    }

    /**
     * Get the album mapper.
     *
     * @return Photo\Mapper\Album
     */
    public function getAlbumMapper()
    {
        return $this->sm->get('photo_mapper_album');
    }

    /**
     * Gets the photo service.
     *
     * @return Photo\Service\Photo
     */
    public function getPhotoService()
    {
        return $this->getServiceManager()->get("photo_service_photo");
    }

}
