<?php

namespace Photo\Service;

use Application\Service\AbstractService;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Photo\Model\Album as AlbumModel;
use Photo\Model\Photo as PhotoModel;

/**
 * Album service.
 */
class Album extends AbstractService
{

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
     * Get the photo mapper.
     *
     * @return \Photo\Mapper\Photo
     */
    public function getPhotoMapper()
    {
        return $this->sm->get('photo_mapper_photo');
    }

    /**
     * Gets an album using the album id
     * 
     * @param integer $id the id of the album
     * @return Photo\Model\Album album matching the given id
     */
    public function getAlbum($id)
    {
        return $this->getAlbumMapper()->getAlbumById($id);
    }

    /**
     * Get all the albums in the root directory
     * 
     * @return array of albums
     */
    public function getAlbums()
    {
        return $this->getAlbumMapper()->getRootAlbums();
    }

    /**
     * Get all photos in an album
     * @param Photo\Model\Album $album the album to get the photos from
     * @return array of Photo\Model\Album
     */
    public function getPhotos($album)
    {
        return $this->getPhotoMapper()->getAlbumPhotos($album);
    }

    /**
     * Get a recusive list of all (sub)albums
     * 
     * @return multi-level array of albums
     */
    public function getAlbumTree($album = null)
    {
        $albums = array();
        if ($album !== null) {
            $subAlbums = $this->getAlbumMapper()->getSubAlbums($album);
            foreach ($subAlbums as $album) {
                $albums[$album] = getAlbumTree($album);
            }
        } else {
            foreach ($this->getAlbums() as $album) {
                $albums[$album] = getAlbumTree($album);
            }
        }
        return $albums;
    }

    public function getCreateAlbumForm()
    {
        //TODO: permissions
        return $this->sm->get('photo_form_album_create');
    }

}
