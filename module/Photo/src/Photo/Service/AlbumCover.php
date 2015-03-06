<?php

namespace Photo\Service;

use Application\Service\AbstractService;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Photo\Model\Album as AlbumModel;
use Photo\Model\Photo as PhotoModel;

/**
 * Album cover services. Used for (re)generating album covers.
 * 
 */
class AlbumCover extends AbstractService
{
    public function generateCover($albumId) {
        
    }
    
    public function resizeCropPhoto() {
        
    }
    
    
    
}
