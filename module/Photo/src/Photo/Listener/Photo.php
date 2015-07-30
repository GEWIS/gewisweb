<?php

namespace Photo\Listener;

/**
 * Doctrine event listener class for Photo entities.
 * This class is instantiated by the doctrine EventManager.
 * Do not instantiate this class manually.
 */
class Photo
{
    /**
     * Updates the date in the album object.
     */
    public function prePersist($photo, $event)
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
    }
}