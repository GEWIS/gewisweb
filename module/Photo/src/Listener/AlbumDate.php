<?php

declare(strict_types=1);

namespace Photo\Listener;

use Doctrine\ORM\Event\PrePersistEventArgs;
use Photo\Model\Album as AlbumModel;
use Photo\Model\Photo as PhotoModel;

/**
 * Doctrine event listener class for Album and Photo entities.
 * Do not instantiate this class manually.
 */
class AlbumDate
{
    public function prePersist(PrePersistEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getObject();

        if ($entity instanceof AlbumModel) {
            $this->albumPersisted($entity);
        } elseif ($entity instanceof PhotoModel) {
            $this->photoPersisted($entity);
        }
    }

    /**
     * Updates the dates on the parent album if it exists.
     */
    protected function albumPersisted(AlbumModel $album): void
    {
        $parent = $album->getParent();
        if (null === $parent) {
            return;
        }

        if (null !== $album->getStartDateTime()) {
            if (
                null === $parent->getStartDateTime()
                || $parent->getStartDateTime()->getTimestamp() > $album->getStartDateTime()->getTimeStamp()
            ) {
                $parent->setStartDateTime($album->getStartDateTime());
            }
        }

        if (null === $album->getEndDateTime()) {
            return;
        }

        if (
            null !== $parent->getEndDateTime()
            && $parent->getEndDateTime()->getTimestamp() >= $album->getEndDateTime()->getTimeStamp()
        ) {
            return;
        }

        $parent->setEndDateTime($album->getEndDateTime());
    }

    /**
     * Updates the dates on the parent album.
     */
    protected function photoPersisted(PhotoModel $photo): void
    {
        $album = $photo->getAlbum();
        // Update start and end date if the added photo is newer or older
        $albumStartDateTime = $album->getStartDateTime();
        if (
            null === $albumStartDateTime
            || $albumStartDateTime->getTimestamp() > $photo->getDateTime()->getTimeStamp()
        ) {
            $album->setStartDateTime($photo->getDateTime());
        }

        $albumEndDateTime = $album->getEndDateTime();
        if (
            null === $albumEndDateTime
            || $albumEndDateTime->getTimestamp() < $photo->getDateTime()->getTimeStamp()
        ) {
            $photo->getAlbum()->setEndDateTime($photo->getDateTime());
        }

        $this->albumPersisted($album);
    }
}
