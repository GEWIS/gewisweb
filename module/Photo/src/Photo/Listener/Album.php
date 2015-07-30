<?php

namespace Photo\Listener;

use Doctrine\ORM\Mapping as ORM;

class Album
{
    /**
     * Updates the albumCount and dates in the parent album object.
     *
     * @ORM\PrePersist()
     * @ORM\PostUpdate()
     */
    public function updateOnAdd($album, $event)
    {
        $parent = $album->getParent();
        if (!is_null($parent) && !is_null($album->getStartDateTime())) {
            $parent->setAlbumCount($parent->getAlbumCount() + 1);
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

    /**
     * Updates the albumCount in the parent album object.
     *
     * @ORM\PreRemove()
     * @ORM\PreUpdate()
     */
    public function updateOnRemove($album, $event)
    {
        $parent = $album->getParent();
        if (!is_null($parent)) {
            $parent->setAlbumCount($parent->getAlbumCount() - 1);
        }
    }
}