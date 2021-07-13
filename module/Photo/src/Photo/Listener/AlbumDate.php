<?php

namespace Photo\Listener;

use Photo\Model\Album;
use Photo\Model\Photo;

/**
 * Doctrine event listener class for Album and Photo entities.
 * Do not instantiate this class manually.
 */
class AlbumDate
{
    public function prePersist($eventArgs)
    {
        $entity = $eventArgs->getEntity();
        if ($entity instanceof Album) {
            $this->albumPersisted($entity);
        } elseif ($entity instanceof Photo) {
            $this->photoPersisted($entity);
        }
    }

    /**
     * Updates the dates on the parent album if it exists.
     *
     * @param Album $album
     */
    protected function albumPersisted($album)
    {
        $parent = $album->getParent();
        if (!is_null($parent)) {
            if (
                is_null($parent->getStartDateTime()) || $parent->getStartDateTime()->getTimestamp() >
                $album->getStartDateTime()->getTimeStamp()
            ) {
                $parent->setStartDateTime($album->getStartDateTime());
            }
            if (
                is_null($parent->getEndDateTime()) || $parent->getEndDateTime()->getTimestamp() <
                $album->getEndDateTime()->getTimeStamp()
            ) {
                $parent->setEndDateTime($album->getEndDateTime());
            }
        }
    }

    /**
     * Updates the dates on the parent album.
     *
     * @param Photo $photo
     */
    protected function photoPersisted($photo)
    {
        $album = $photo->getAlbum();
        // Update start and end date if the added photo is newer or older
        $albumStartDateTime = $album->getStartDateTime();
        if (is_null($albumStartDateTime) || $albumStartDateTime->getTimestamp() > $photo->getDateTime()->getTimeStamp()) {
            $album->setStartDateTime($photo->getDateTime());
        }

        $albumEndDateTime = $album->getEndDateTime();
        if (is_null($albumEndDateTime) || $albumEndDateTime->getTimestamp() < $photo->getDateTime()->getTimeStamp()) {
            $photo->getAlbum()->setEndDateTime($photo->getDateTime());
        }

        $this->albumPersisted($album);
    }
}
