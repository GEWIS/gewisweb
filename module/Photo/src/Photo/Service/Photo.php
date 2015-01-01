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
        return $this->sm->get('photo_mapper_photo');
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
     * 
     * @param string $path
     * @return the path at which the photo should be saved
     */
    protected function generateStoragePath($path)
    {
        $config = $this->getConfig();
        //TODO: check if this is fast enough
        $hash = sha1_file($path);
        /**
         * the hash is split to obtain a path 
         * like 92/cfceb39d57d914ed8b14d0e37643de0797ae56.jpg
         */
        $directory = substr($hash, 0, 2);
        if (!file_exists($config['upload_dir'] . '/' . $directory)) {
            mkdir($config['upload_dir'] . '/' . $directory);
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
        $config = $this->getConfig();
        $storage_path = $this->generateStoragePath($path);
        $photo = new PhotoModel();
        $photo->setAlbum($target_album);
        $photo = $this->populateMetaData($photo, $path);
        $photo->setPath($storage_path);

        $mapper = $this->getPhotoMapper();
        /**
         * TODO: optionally we could use a transactional query here to make it
         * completely failsafe in case something goes wrong when moving the
         * photo in the storeUploadedPhoto function. However it's very unlikely
         * anything will go wrong when moving the photo.
         */
        $mapper->persist($photo);
        $mapper->flush();
        rename($path, $config['upload_dir'] . '/' . $storage_path);
        return $photo;
    }

    /**
     * 
     * @param \Photo\Model\Photo $photo the photo to add the metadata to
     * @return \Photo\Model\Photo the photo with the added metadata
     */
    protected function populateMetadata($photo, $temp_path)
    {
        //TODO: fetch metadata from photo and add it
        //placeholder for now
        $photo->setDate(new \DateTime("2014-12-12 12:13:12.424242"));
        return $photo;
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
     * Replaces empty cover photos with random ones.
     * 
     * @param Photo\Model\Album array $albums
     * @return Photo\Model\Album array
     */
    public function populateCoverPhotos($albums)
    {
        foreach ($albums as $album) {
            if (is_null($album->getCover())) {
                $album->setCover($this->getPhotoMapper()->getRandomPhoto($album));
            }
        }
        return $albums;
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

}
