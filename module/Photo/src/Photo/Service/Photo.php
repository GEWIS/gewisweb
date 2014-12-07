<?php

namespace Photo\Service;

use Application\Service\AbstractService;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Photo\Model\Album as AlbumModel;
use Photo\Model\Photo as PhotoModel;

/**
 * Photo service.
 */
class Photo extends AbstractService
{

    /**
     * Get the photo mapper.
     *
     * @return \Photo\Mapper\Photo
     */
    public function getPhotoMapper()
    {
        return $this->sm->get('photo_mapper_album');
    }

    /**
     * 
     * @param string $path
     * @param \Photo\Model\Photo $photo
     * @return the path at which the photo should be saved
     */
    protected function generateStoragePath($path, $photo)
    {
        $config = $this->getConfig();
        //TODO: check if this is fast enough
        $hash = sha1_file($path);
        /**
         * the hash is split to obtain a path 
         * like 92/cfceb39d57d914ed8b14d0e37643de0797ae56.jpg
         */
        $directory = $config['upload_dir'] . '/' . substr($hash, 0, 2);
        if (!file_exists($directory)) {
            mkdir($directory);
        }
        $storage_path = $directory . '/' . substr($hash, 2) . '.' . strtolower(end(explode('.', $path)));
        return $storage_path;
    }

    /**
     * Move the uploaded photo to the storage and store it in the database.
     * All upload actions should use this function to prevent "ghost" files 
     * or database entries
     * @param string $path the tempoary path of the uploaded photo
     * @param \Photo\Model\Album $album the album to save the photo in
     * @return \Photo\Model\Photo
     */
    public function storeUploadedPhoto($path, $target_album)
    {
        $photo = $this->createPhotoEntity($path, target_album);
        $storage_path = $this->generateStoragePath($path, $photo);
        rename($path, $storage_path);
    }

    /**
     * Creates an instance of a Photo and saves it in the database
     * @param type $path
     * @return \Photo\Model\Photo
     */
    protected function createPhotoEntity($path, $target_album)
    {
        $photo = new PhotoModel();
        $photo->setAlbum($target_album);
        $photo = $this->populateMetaData($photo);
        $mapper = $this->getPhotoMapper();
        /**
         * TODO: optionally we could use a transactional query here to make it
         * completely failsafe in case something goes wrong when moving the
         * photo in the storeUploadedPhoto function. However it's very unlikely
         * anything will go wrong when moving the photo.
         */
        $mapper->persist($photo);
        $mapper->flush();
    }

    /**
     * 
     * @param \Photo\Model\Photo $photo the photo to add the metadata to
     * @return \Photo\Model\Photo the photo with the added metadata
     */
    protected function populateMetadata($photo)
    {
        //TODO: fetch metadata from photo and add it
        return $photo;
    }

}
