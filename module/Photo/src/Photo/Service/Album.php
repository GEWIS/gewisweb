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
     * @param integer $start the result to start at
     * @param integer $maxResults max amount of results to return, null for infinite
     * @return array of albums
     */
    public function getAlbums($album = null, $start = 0, $maxResults = null)
    {
        if ($album == null) {
            return $this->getAlbumMapper()->getRootAlbums();
        } else {
            return $this->getAlbumMapper()->getSubAlbums($album, $start, $maxResults);
        }
    }

    /**
     * Creates a new album.
     * 
     * @param String $name The name of the new album.
     * @param Photo\Model\Album $parent The parent of this album, if any.
     * @return The new album.
     */
    public function createAlbum($name, $parent = null)
    {

        $album = new AlbumModel();
        $album->setName($name);
        if (!is_null($parent)) {
            $album->setParent($parent);
        }

        $mapper = $this->getAlbumMapper();
        $mapper->persist($album);
        $mapper->flush();
        return $album;
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
            foreach ($subAlbums as $subAlbum) {
                $albums[] = array('albumName' => $subAlbum->getName(), 'subAlbums' => $this->getAlbumTree($subAlbum));
            }
        } else {
            foreach ($this->getAlbums() as $album) {
                $albums[$album->getId()] = $this->getAlbumTree($album);
            }
        }
        return $albums;
    }

    public function getCreateAlbumForm()
    {
        //TODO: permissions
        return $this->sm->get('photo_form_album_create');
    }

    /**
     * Get the PhotoImport form.
     *
     * @return \Photo\Form\PhotoImport
     */
    public function getPhotoImportForm()
    {
        return $this->sm->get('photo_form_import_folder');
    }

    /**
     * Get the photo config
     *
     * @return array
     */
    public function getConfig()
    {
        $config = $this->sm->get('config');
        return $config['photo'];
    }

}
