<?php

namespace Photo\Service;

use _HumbugBoxa991b62ce91e\Nette\FileNotFoundException;
use Application\Service\AbstractAclService;
use http\Exception\InvalidArgumentException;
use Photo\Model\Photo as PhotoModel;
use Imagick;

/**
 * Admin service for all photo admin related functions.
 */
class Admin extends AbstractAclService
{
    /**
     * Move the uploaded photo to the storage and store it in the database.
     * All upload actions should use this function to prevent "ghost" files
     * or database entries
     *
     * @param string $path the temporary path of the uploaded photo
     * @param \Photo\Model\Album $targetAlbum the album to save the photo in
     * @param boolean $move whether to move the photo instead of copying it
     *
     * @return \Photo\Model\Photo|boolean
     */
    public function storeUploadedPhoto($path, $targetAlbum, $move = false)
    {
        if (!$this->isAllowed('add', 'photo')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('Not allowed to add photos.')
            );
        }

        $config = $this->getConfig();
        $storagePath = $newPath = $this->getFileStorageService()->storeFile($path, false);
        //check if photo exists already in the database
        $photo = $this->getPhotoMapper()->getPhotoByData($storagePath, $targetAlbum);
        //if the returned object is null, then the photo doesn't exist
        if (is_null($photo)) {
            $photo = new PhotoModel();
            $photo->setAlbum($targetAlbum);
            $photo = $this->getMetadataService()->populateMetaData($photo, $path);
            $photo->setPath($storagePath);

            $mapper = $this->getPhotoMapper();
            $mapper->getConnection()->beginTransaction();
            try {
                /*
                 * Create and set the storage paths for thumbnails.
                 */
                $photo->setLargeThumbPath($this->createThumbnail(
                    $path,
                    $config['large_thumb_size']['width'],
                    $config['large_thumb_size']['height']
                ));
                $photo->setSmallThumbPath($this->createThumbnail(
                    $path,
                    $config['small_thumb_size']['width'],
                    $config['small_thumb_size']['height']
                ));

                if ($move) {
                    unlink($path);
                }

                $mapper->persist($photo);
                $mapper->flush();
                $mapper->getConnection()->commit();
            } catch (\Exception $e) {
                // Rollback if anything went wrong
                $mapper->getConnection()->rollBack();
                $this->getPhotoService()->deletePhotoFiles($photo);
                return false;
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
     *
     * @return string the path of the created thumbnail
     */
    protected function createThumbnail($path, $width, $height)
    {
        $image = new Imagick($path);
        $image->thumbnailImage($width, $height, true);
        $image->setimageformat("png");
        //Tempfile is used to generate sha1, not sure this is the best method
        $tempFileName = sys_get_temp_dir() . '/ThumbImage' . rand() . '.png';
        $image->writeImage($tempFileName);
        $newPath = $this->getFileStorageService()->storeFile($tempFileName);

        return $newPath;
    }

    /**
     * Stores an directory in $target_album.
     * If any subdirectory is present, it will be stored in a new album,
     * with the (temporary) name of the directory.
     * (i.e. the function is applied recursively)
     *
     * @param string $path The path of the directory.
     * @param \Photo\Model\Album $targetAlbum album The album to store the photos.
     *
     * @throws \Exception on invalid path
     */
    public function storeUploadedDirectory($path, $targetAlbum)
    {
        if (!$this->isAllowed('import', 'photo')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('Not allowed to import photos.')
            );
        }

        $albumService = $this->getAlbumService();
        $image = new \Zend\Validator\File\IsImage(['magicFile' => false]);
        if ($handle = opendir($path)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {

                    $subPath = $path . '/' . $entry;
                    if (is_dir($subPath)) {
                        //TODO: this no longer works
                        $subAlbum = $albumService->createAlbum($entry, $targetAlbum);
                        $this->storeUploadedDirectory($subPath, $subAlbum);
                    } elseif ($image->isValid($subPath)) {
                        $this->storeUploadedPhoto($subPath, $targetAlbum);
                    }
                }
            }
            closedir($handle);
        } else {
            $translator = $this->getTranslator();
            throw new InvalidArgumentException(
                $translator->translate('The specified path is not valid')
            );
        }
    }

    public function upload($files, $album)
    {
        $this->checkUploadAllowed();

        $imageValidator = new \Zend\Validator\File\IsImage(
            ['magicFile' => false]
        );
        $extensionValidator = new \Zend\Validator\File\Extension(
            ['JPEG', 'JPG', 'JFIF', 'TIFF', 'RIF', 'GIF', 'BMP', 'PNG']
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
                throw new \InvalidArgumentException(
                    $translator->translate('The uploaded file does not have a valid extension')
                );
            }
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    $translator->translate("The uploaded file is not a valid image \nError: %s"),
                    implode(',', array_values($imageValidator->getMessages()))
                )
            );
        }
    }

    /**
     * Checks if the current user is allowed to upload photos.
     */
    public function checkUploadAllowed()
    {
        if (!$this->isAllowed('upload', 'photo')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('Not allowed to upload photos.')
            );
        }
    }

    /**
     * Get the photo config, as used by this service.
     *
     * @return array containing the config for the module
     */
    public function getConfig()
    {
        $config = $this->sm->get('config');

        return $config['photo'];
    }

    /**
     * Get the photo mapper.
     *
     * @return \Photo\Mapper\Photo
     */
    public function getPhotoMapper()
    {
        return $this->sm->get('photo_mapper_photo');
    }

    /**
     * Get the album mapper.
     *
     * @return \Photo\Mapper\Album
     */
    public function getAlbumMapper()
    {
        return $this->sm->get('photo_mapper_album');
    }

    /**
     * Get the tag mapper.
     *
     * @return \Photo\Mapper\Tag
     */
    public function getTagMapper()
    {
        return $this->sm->get('photo_mapper_tag');
    }

    /**
     * Gets the metadata service.
     *
     * @return \Photo\Service\Metadata
     */
    public function getMetadataService()
    {
        return $this->sm->get('photo_service_metadata');
    }

    /**
     * Gets the album service.
     *
     * @return \Photo\Service\Album
     */
    public function getAlbumService()
    {
        return $this->sm->get('photo_service_album');
    }

    /**
     * Get the member service.
     *
     * @return \Decision\Service\Member
     */
    public function getMemberService()
    {
        return $this->sm->get('decision_service_member');
    }

    /**
     * Gets the photo service.
     *
     * @return \Photo\Service\Photo
     */
    public function getPhotoService()
    {
        return $this->sm->get('photo_service_photo');
    }

    /**
     * Gets the storage service.
     *
     * @return \Application\Service\Storage
     */
    public function getFileStorageService()
    {
        return $this->sm->get('application_service_storage');
    }

    /**
     * Get the default resource ID.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'admin';
    }

    /**
     * Get the Acl.
     *
     * @return \Zend\Permissions\Acl\Acl
     */
    public function getAcl()
    {
        return $this->sm->get('photo_acl');
    }
}
