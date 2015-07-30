<?php

namespace Photo\Listener;

/**
 * Doctrine event listener class for Album entities.
 * This class is instantiated by the doctrine EventManager.
 * Do not instantiate this class manually.
 */
class Album
{
    /**
     * Updates the dates in the parent album object.
     */
    public function prePersist($album, $event)
    {
        $parent = $album->getParent();
        if (!is_null($parent) && !is_null($album->getStartDateTime())) {
            if (is_null($parent->getStartDateTime()) || $parent->getStartDateTime()->getTimestamp() >
                $album->getStartDateTime()->getTimeStamp()
            ) {
                $parent->setStartDateTime($album->getStartDateTime());
            }
            if (is_null($parent->getEndDateTime()) || $parent->getEndDateTime()->getTimestamp() <
                $album->getEndDateTime()->getTimeStamp()
            ) {
                $parent->setEndDateTime($album->getEndDateTime());
            }
        }
    }
}