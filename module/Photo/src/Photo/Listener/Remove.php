<?php

namespace Photo\Listener;

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
        if ($entity instanceof \Photo\Model\Album) {
            $this->albumRemoved($entity);
        } elseif ($entity instanceof \Photo\Model\Photo) {
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