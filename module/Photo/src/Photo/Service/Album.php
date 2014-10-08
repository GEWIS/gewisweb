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
class Album extends AbstractService {

    /**
     * Get the album mapper.
     *
     * @return \Photo\Mapper\Album
     */
    public function getAlbumMapper() {
        return $this->sm->get('photo_mapper_album');
    }

    /**
     * Get all the albums in the root directory
     * 
     * @return array of albums
     */
    public function getAlbums() {
        return $this->getAlbumMapper()->getRootAlbums();
    }

    /**
     * Get a recusive list of all (sub)albums
     * 
     * @return multi-level array of albums
     */
    public function getAlbumTree($album = null) {
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

    public function getCreateAlbumForm() {
        //TODO: permissions
        return $this->sm->get('photo_form_album_create');
    }

    /*
    public function createAlbum($name, $parent = null) {
        //TODO: Create actual directory
        $album = new AlbumModel();
        $album->setName($name);
        $album->setParent($parent);
        $album->setDate(new \DateTime()); //TODO: specify date time (range)
        $mapper = $this->getAlbumMapper();
        $mapper->persist($album);
        $mapper->flush();
    }*/

}
