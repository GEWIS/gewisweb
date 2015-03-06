<?php

namespace Photo\Service;

use Application\Service\AbstractService;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Photo\Model\Album as AlbumModel;
use Photo\Model\Photo as PhotoModel;
use Imagick;

/**
 * Album cover services. Used for (re)generating album covers.
 * 
 */
class AlbumCover extends AbstractService
{

    public function createCover($album)
    {
        $cover = $this->generateCover($album);
        $tempFileName = sys_get_temp_dir() . '/ThumbImage' . rand() . '.png';
        $cover->writeImage($tempFileName);
        $newPath = $this->getPhotoService()->generateStoragePath($tempFileName);
        $config = $this->getConfig();
        rename($tempFileName, $config['upload_dir'] . '/' . $newPath);
        return $newPath;
    }

    protected function generateCover($album)
    {
        $config = $this->getConfig();
        $cols = $config['album_cover']['cols'];
        $rows = $config['album_cover']['rows'];
        $count = $cols * $rows;
        $images = $this->getImages($album, $count);
        //if there are not enough images to fill the matrix, reduce the rows and columns
        while (($cols > 1 || $rows > 1) && count($images) < $count) {
            if ($cols < $rows) {
                $rows--;
            } else {
                $cols--;
            }
            $count = $rows * $cols;
        }
        $innerWidth = $config['album_cover']['width'] - 2 * $config['album_cover']['outer_border'];
        $innerHeight = $config['album_cover']['height'] - 2 * $config['album_cover']['outer_border'];
        $image_width = floor(($innerWidth - ($cols - 1) * $config['album_cover']['inner_border']) / $cols);
        $image_height = floor(($innerHeight - ($rows - 1) * $config['album_cover']['inner_border']) / $rows);
        //increase outer border due to flooring of image dimensions
        $outer_border_x = $config['album_cover']['outer_border'] + ceil(($innerWidth - ($cols * $image_width + ($cols - 1) * $config['album_cover']['inner_border'])) / 2);
        $outer_border_y = $config['album_cover']['outer_border'] + ceil(($innerHeight - ($rows * $image_height + ($rows - 1) * $config['album_cover']['inner_border'])) / 2);
        // Make a blank canvas
        $target = new Imagick();
        $target->newImage($config['album_cover']['width'], $config['album_cover']['height'], $config['album_cover']['background']);
        $index = 0;
        if (count($images) >= $count) {
            for ($x = 0; $x < $cols; $x++) {
                for ($y = 0; $y < $rows; $y++) {
                    $image = $this->resizeCropImage($images[$index], $image_width, $image_height);
                    $index++;
                    $target->compositeImage($image, imagick::COMPOSITE_COPY, ($image_width + $config['album_cover']['inner_border']) * $x + $outer_border_x, ($image_height + $config['album_cover']['inner_border']) * $y + $outer_border_y);
                }
            }
        }
        $target->setImageFormat("png");
        return $target;
    }

    /**
     * Specialized function to rezie and crop photos such that they always
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
        $resizeWidth = max($width, floor($image->getImageGeometry()['width'] / ($image->getImageGeometry()['height'] / $height)));
        $resizeHeight = max($height, floor($image->getImageGeometry()['height'] / ($image->getImageGeometry()['width'] / $width)));
        $image->resizeImage($resizeWidth, $resizeHeight, Imagick::FILTER_LANCZOS, 1);
        $cropX = 0;
        $cropY = 0;
        if ($width < $image->getImageGeometry()['width']) {
            $cropX = floor(($image->getImageGeometry()['width'] - $width) / 2);
        }
        if ($height < $image->getImageGeometry()['height']) {
            $cropY = floor(($image->getImageGeometry()['height'] - $height) / 2);
        }
        $image->cropImage($width, $height, $cropX, $cropY);
        //this second resize may not be needed, needs testing.
        $image->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 1);
        return $image;
    }

    /**
     * Returns the images needed to fill the album cover
     * 
     * @param Photo\Model\Album $album
     */
    protected function getImages($album, $count)
    {
        $config = $this->getConfig();
        $photos = $this->getAlbumMapper()->getRandomAlbumPhotos($album, $count);
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
