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
     * @param \Photo\Model\Album $target_album the album to save the photo in
     * @return \Photo\Model\Photo
     */
    public function storeUploadedPhoto($path, $target_album)
    {
        $config = $this->getConfig();
        $storage_path = $this->generateStoragePath($path);
        //check if photo exists already in the database
        echo  $storage_path."<br><br>".$target_album->getId();
        $photo = $this->getPhotoMapper()->getPhotoByData($storage_path, $target_album);
        //if the returned object is null, then the photo doesn't exist
        if (is_null($photo)) {
            $photo = new PhotoModel();
            $photo->setAlbum($target_album);
            $photo = $this->getMetadataService()->populateMetaData($photo, $path);
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
        } else { echo  "<h1>exists</h1>"; }
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
     * Get all photos in an album
     * 
     * @param Photo\Model\Album $album the album to get the photos from
     * @param integer $start the result to start at
     * @param integer $max_results max amount of results to return, null for infinite
     * @return array of Photo\Model\Album
     */
    public function getPhotos($album, $start = 0, $max_results = null)
    {
        $config = $this->getConfig();
        return $this->getPhotoMapper()->getAlbumPhotos($album, $start, $max_results);
    }

    /**
     * 
     * @param type $id the id of the photo to retrieve
     * @return array of data about the photo, which is usefull inside a view
     */
    public function getPhotoData($id)
    {
        $photo = $this->getPhoto($id);
        if (!is_null($photo)) {
            $next = $this->getNextPhoto($photo);
            $previous = $this->getPreviousPhoto($photo);
        }
        //we'll fix this ugly thing later vv
        $basedir = str_replace("public", "", $this->getConfig()['upload_dir']);

        return array(
            'photo' => $photo,
            'basedir' => $basedir,
            'next' => $next,
            'previous' => $previous
        );
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
        return $this->getServiceLocator()->get("photo_service_metadata");
    }

}
