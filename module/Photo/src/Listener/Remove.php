<?php

declare(strict_types=1);

namespace Photo\Listener;

use Doctrine\ORM\Event\PreRemoveEventArgs;
use Photo\Model\Album as AlbumModel;
use Photo\Model\Photo as PhotoModel;
use Photo\Service\Album as AlbumService;
use Photo\Service\Photo as PhotoService;

/**
 * Doctrine event listener class for Album and Photo entities.
 * Do not instantiate this class manually.
 */
class Remove
{
    public function __construct(
        private readonly PhotoService $photoService,
        private readonly AlbumService $albumService,
    ) {
    }

    public function preRemove(PreRemoveEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getObject();

        if ($entity instanceof AlbumModel) {
            $this->albumRemoved($entity);
        } elseif ($entity instanceof PhotoModel) {
            $this->photoRemoved($entity);
        }
    }

    protected function photoRemoved(PhotoModel $photo): void
    {
        $this->photoService->deletePhotoFiles($photo);
    }

    protected function albumRemoved(AlbumModel $album): void
    {
        $this->albumService->deleteAlbumCover($album);
    }
}
