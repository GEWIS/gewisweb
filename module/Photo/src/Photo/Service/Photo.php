<?php

namespace Photo\Service;

use Application\Service\AbstractService;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Photo\Model\Album as AlbumModel;
use Photo\Model\Photo as PhotoModel;
use Imagick;

/**
 * Photo service.
 */
class Photo extends AbstractService
{

    /**
     * Get the photo mapper.
     *
     * @return Photo\Mapper\Photo
     */
    public function getPhotoMapper()
    {
        return $this->sm->get('photo_mapper_photo');
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
     * 
     * @param integer $id the id of the album
     * @return Photo\Model\Photo photo matching the given id
     */
    public function getPhoto($id)
    {
        return $this->getPhotoMapper()->getPhotoById($id);
    }

    /**
     * Generates CFS paths
     *
     * @param string $path The path of the photo to generate the path for
     * @return the path at which the photo should be saved
     */
    public function generateStoragePath($path)
    {
        $config = $this->getConfig();
        $hash = sha1_file($path);
        /**
         * the hash is split to obtain a path 
         * like 92/cfceb39d57d914ed8b14d0e37643de0797ae56.jpg
         */
        $directory = substr($hash, 0, 2);
        if (!file_exists($config['upload_dir'] . '/' . $directory)) {
            mkdir($config['upload_dir'] . '/' . $directory);
        }
        $parts = explode('.', $path);
        $fileType = end($parts);
        $storagePath = $directory . '/' . substr($hash, 2) . '.' . strtolower($fileType);
        return $storagePath;
    }

    /**
     * Move the uploaded photo to the storage and store it in the database.
     * All upload actions should use this function to prevent "ghost" files 
     * or database entries
     * @param string $path the temporary path of the uploaded photo
     * @param Photo\Model\Album $targetAlbum the album to save the photo in
     * @param boolean $move whether to move the photo instead of copying it
     * @return Photo\Model\Photo
     */
    public function storeUploadedPhoto($path, $targetAlbum, $move = false)
    {
        $config = $this->getConfig();
        $storagePath = $this->generateStoragePath($path);
        //check if photo exists already in the database
        $photo = $this->getPhotoMapper()->getPhotoByData($storagePath, $targetAlbum);
        //if the returned object is null, then the photo doesn't exist
        if (is_null($photo)) {
            $photo = new PhotoModel();
            $photo->setAlbum($targetAlbum);
            $photo = $this->getMetadataService()->populateMetaData($photo, $path);
            $photo->setPath($storagePath);

            //Create and set the storage paths for thumbnails.
            //Currently, the maximum sizes of the old website are used. These
            //values are dependant on the design.
            $photo->setLargeThumbPath($this->createThumbnail($path, $config['large_thumb_size']['width'], $config['large_thumb_size']['height']));
            $photo->setSmallThumbPath($this->createThumbnail($path, $config['small_thumb_size']['width'], $config['small_thumb_size']['height']));
            $mapper = $this->getPhotoMapper();
            /**
             * TODO: optionally we could use a transactional query here to make it
             * completely fail-safe in case something goes wrong when moving the
             * photo in the storeUploadedPhoto function. However it's very unlikely
             * anything will go wrong when moving the photo.
             */
            $mapper->persist($photo);
            $mapper->flush();
            if ($move) {
                rename($path, $config['upload_dir'] . '/' . $storagePath);
            } else {
                copy($path, $config['upload_dir'] . '/' . $storagePath);
            }
        }
        return $photo;
    }

    /**
     * Creates and stores a thumbnail of specified maximum size from a stored 
     * image 
     * 
     * @param string $path the path of the original image
     * @param int $width the maximum width of the thumbnail (in px)
     * @param int $height the maximum height of the thumbnail (in px)
     * @return string the path of the created thumbnail
     */
    protected function createThumbnail($path, $width, $height)
    {

        $image = new Imagick($path);
        $image->thumbnailImage($width, $height, true);
        $image->setimageformat("png");
        //Tempfile is used to generate sha1, not sure this is the best method
        $tempFileName = sys_get_temp_dir() . '/ThumbImage' . rand() .'.png';
        $image->writeImage($tempFileName);
        $newPath = $this->generateStoragePath($tempFileName);
        $config = $this->getConfig();
        rename($tempFileName, $config['upload_dir'] . '/' . $newPath);
        return $newPath;
    }

    /**
     * Stores an directory in $target_album.
     * If any subdirectory is present, it will be stored in a new album, 
     * with the (temporary) name of the directory.
     * (i.e. the function is applied recursively)
     * @param string $path The path of the directory.
     * @param Photo\Model\Album $target_album The album to store the photos.
     * @throws \Exception on invalid path
     */
    public function storeUploadedDirectory($path, $targetAlbum)
    {
        $albumService = $this->getAlbumService();
        $image = new \Zend\Validator\File\IsImage(array('magicFile' => false));
        if ($handle = opendir($path)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {

                    $subPath = $path . '/' . $entry;
                    if (is_dir($subPath)) {
                        $subAlbum = $albumService->createAlbum($entry, $targetAlbum);
                        $this->storeUploadedDirectory($subPath, $subAlbum);
                    } elseif ($image->isValid($subPath)) {
                        $this->getPhotoService()->storeUploadedPhoto($subPath, $targetAlbum);
                    }
                }
            }
            closedir($handle);
        } else {
            $translator = $this->getTranslator();
            throw new \Exception(
            $translator->translate('The specified path is not valid')
            );
        }
    }

    public function upload($files, $album)
    {
        $imageValidator = new \Zend\Validator\File\IsImage(
                array('magicFile' => false)
        );
        $extensionValidator = new \Zend\Validator\File\Extension(
                array('JPEG', 'JPG', 'JFIF', 'TIFF', 'RIF', 'GIF', 'BMP', 'PNG')
        );
        $translator = $this->getTranslator();

        if ($files['file']['error'] !== 0) {
            throw new \Exception(
            $translator->translate('An unknown error occurred during uploading (' . $files['file']['error'] . ')')
            );
        }
        /**
         * We re-add the original extension so it can be preserved later on
         * when moving the file.
         */
        $extension = explode('/', $files['file']['type'])[1];
        $path = $files['file']['tmp_name'] . '.' . $extension;
        move_uploaded_file($files['file']['tmp_name'], $path);

        if ($imageValidator->isValid($path)) {
            if ($extensionValidator->isValid($path)) {
                $this->storeUploadedPhoto($path, $album, true);
            } else {
                throw new \Exception(
                $translator->translate('The uploaded file does not have a valid extension')
                );
            }
        } else {
            throw new \Exception(
            $translator->translate('The uploaded file is not a valid image')
            );
        }
    }

    /**
     * Returns the next photo in the album to display
     * 
     * @param \Photo\Model\Photo $photo 
     */
    public function getNextPhoto($photo)
    {
        return $this->getPhotoMapper()->getNextPhoto($photo);
    }

    /**
     * Returns the previous photo in the album to display
     * 
     * @param \Photo\Model\Photo $photo 
     */
    public function getPreviousPhoto($photo)
    {
        return $this->getPhotoMapper()->getPreviousPhoto($photo);
    }

    /**
     * Get all photos in an album
     * 
     * @param Photo\Model\Album $album the album to get the photos from
     * @param integer $start the result to start at
     * @param integer $maxResults max amount of results to return, null for infinite
     * @return array of Photo\Model\Album
     */
    public function getPhotos($album, $start = 0, $maxResults = null)
    {
        return $this->getAlbumMapper()->getAlbumPhotos($album, $start, $maxResults);
    }

    /**
     * 
     * @param int $id the id of the photo to retrieve
     * @return array of data about the photo, which is useful inside a view
     */
    public function getPhotoData($id)
    {
        $photo = $this->getPhoto($id);
        if (!is_null($photo)) {
            $next = $this->getNextPhoto($photo);
            $previous = $this->getPreviousPhoto($photo);
        }

        $basedir = $this->getBaseDirectory();

        return array(
            'photo' => $photo,
            'basedir' => $basedir,
            'next' => $next,
            'previous' => $previous
        );
    }

    /**
     * Removes a photo from the database and deletes its files, including thumbs
     * from the server.
     * 
     * @param int $id the id of the photo to delete
     *
     * @return bool indicating whether the delete was successful
     */
    public function deletePhoto($id)
    {
        $config = $this->getConfig();
        $photo = $this->getPhoto($id);
        if (is_null($photo)) {
            return false;
        }
        unlink($config['upload_dir'] . '/' . $photo->getPath());
        unlink($config['upload_dir'] . '/' . $photo->getLargeThumbPath());
        unlink($config['upload_dir'] . '/' . $photo->getSmallThumbPath());
        $this->getPhotoMapper()->deletePhoto($photo->getId());
        $this->getPhotoMapper()->flush();
        return true;

    }

    /**
     * Moves a photo to a new album.
     * 
     * @param int $id the id of the photo
     * @param int $albumId the id of the new album
     *
     * @return bool indicating whether move was successful
     */
    public function movePhoto($id, $albumId)
    {
        $photo = $this->getPhoto($id);
        $album = $this->getAlbumService()->getAlbum($albumId);
        if(is_null($photo) || is_null($album)) {
            return false;
        }

        $photo->setAlbum($album);
        $this->getAlbumMapper()->flush();
        return true;

    }

    /**
     * Gets the base directory from which the photo paths should be requested
     * 
     * @return string
     */
    public function getBaseDirectory()
    {
        $config = $this->getConfig();
        return str_replace('public', '', $config['upload_dir']);
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
     * Gets the metadata service.
     * 
     * @return Photo\Service\Metadata
     */
    public function getMetadataService()
    {
        return $this->getServiceManager()->get('photo_service_metadata');
    }

    /**
     * Gets the album service.
     * 
     * @return Photo\Service\Album
     */
    public function getAlbumService()
    {
        return $this->getServiceManager()->get('photo_service_album');
    }

    /**
     * Gets the album service.
     * 
     * @return Photo\Service\Album
     */
    public function getPhotoService()
    {
        return $this->getServiceManager()->get('photo_service_photo');
    }

}
