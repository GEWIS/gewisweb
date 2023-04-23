<?php

declare(strict_types=1);

namespace Photo\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Photo\Model\{
    Album as AlbumModel,
    Photo as PhotoModel,
};

/**
 * Doctrine event listener class for Album and Photo entities.
 * Do not instantiate this class manually.
 */
class AlbumDate
{
    /**
     * @param LifecycleEventArgs $eventArgs
     */
    public function prePersist(LifecycleEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getEntity();

        if ($entity instanceof AlbumModel) {
            $this->albumPersisted($entity);
        } elseif ($entity instanceof PhotoModel) {
            $this->photoPersisted($entity);
        }
    }

    /**
     * Updates the dates on the parent album if it exists.
     *
     * @param AlbumModel $album
     */
    protected function albumPersisted(AlbumModel $album): void
    {
        $parent = $album->getParent();
        if (!is_null($parent)) {
            if (!is_null($album->getStartDateTime())) {
                if (
                    is_null($parent->getStartDateTime())
                    || $parent->getStartDateTime()->getTimestamp() > $album->getStartDateTime()->getTimeStamp()
                ) {
                    $parent->setStartDateTime($album->getStartDateTime());
                }
            }

            if (!is_null($album->getEndDateTime())) {
                if (
                    is_null($parent->getEndDateTime())
                    || $parent->getEndDateTime()->getTimestamp() < $album->getEndDateTime()->getTimeStamp()
                ) {
                    $parent->setEndDateTime($album->getEndDateTime());
                }
            }
        }
    }

    /**
     * Updates the dates on the parent album.
     *
     * @param PhotoModel $photo
     */
    protected function photoPersisted(PhotoModel $photo): void
    {
        $album = $photo->getAlbum();
        // Update start and end date if the added photo is newer or older
        $albumStartDateTime = $album->getStartDateTime();
        if (
            is_null($albumStartDateTime)
            || $albumStartDateTime->getTimestamp() > $photo->getDateTime()->getTimeStamp()
        ) {
            $album->setStartDateTime($photo->getDateTime());
        }

        $albumEndDateTime = $album->getEndDateTime();
        if (
            is_null($albumEndDateTime)
            || $albumEndDateTime->getTimestamp() < $photo->getDateTime()->getTimeStamp()
        ) {
            $photo->getAlbum()->setEndDateTime($photo->getDateTime());
        }

        $this->albumPersisted($album);
    }
}
