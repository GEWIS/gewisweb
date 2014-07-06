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
     * Get all the albums in the root directory
     * 
     * @return array of albums
     */
    public function getAlbums()
    {
       return $this->getAlbumMapper()->getRootAlbums();
    }
    
    public function createAlbum($name, $parent=null) {
        //TODO: Create actual directory
        $album = new AlbumModel();
        $album->setName($name);
        $album->setParent($parent);
        $album->setDate(new \DateTime()); //TODO: specify date time?
        $mapper=$this->getAlbumMapper();
        $mapper->persist($album);
        $mapper->flush();
    }
}