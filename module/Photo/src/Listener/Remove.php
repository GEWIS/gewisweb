<?php

namespace Photo\Listener;

use Photo\Model\Album;
use Photo\Model\Photo;

/**
 * Doctrine event listener class for Album and Photo entities.
 * Do not instantiate this class manually.
 */
class Remove
{
    /**
     * @var \Photo\Service\Photo
     */
    private $photoService;

    /**
     * @var \Photo\Service\Album
     */
    private $albumService;

    public function __construct(\Photo\Service\Photo $photoService, \Photo\Service\Album $albumService)
    {
        $this->photoService = $photoService;
        $this->albumService = $albumService;
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
        $this->photoService->deletePhotoFiles($photo);
    }

    protected function albumRemoved($album)
    {
        $this->albumService->deleteAlbumCover($album);
    }
}
