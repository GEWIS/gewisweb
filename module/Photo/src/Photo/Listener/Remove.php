<?php

namespace Photo\Listener;

use Photo\Model\Album;
use Photo\Model\Photo;
use Zend\ServiceManager\ServiceManager;

/**
 * Doctrine event listener class for Album and Photo entities.
 * Do not instantiate this class manually.
 */
class Remove
{

    protected $sm;

    public function __construct(ServiceManager $sm)
    {
        $this->sm = $sm;
    }

    public function preRemove($eventArgs)
    {
        $entity = $eventArgs->getEntity();
        if ($entity instanceof Album) {
            $this->albumRemoved($entity);
        } elseif ($entity instanceof Photo) {
            $this->photoRemoved($entity);
        }
    }

    protected function photoRemoved($photo)
    {
        $this->sm->get('photo_service_photo')->deletePhotoFiles($photo);
    }

    protected function albumRemoved($album)
    {
        $this->sm->get('photo_service_album')->deleteAlbumCover($album);
    }
}
