<?php

namespace Photo\Listener;

use Doctrine\ORM\Mapping as ORM;

class Photo
{
    /**
     * Updates the photoCount and date in the album object.
     *
     * @ORM\PrePersist()
     * @ORM\PostUpdate()
     */
    public function updateOnAdd($photo, $event)
    {
        $album = $photo->getAlbum();
        $album->setPhotoCount($album->getPhotoCount() + 1);
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

    /**
     * Updates the photoCount in the album object.
     *
     * @ORM\PreRemove()
     * @ORM\PreUpdate()
     */
    public function updateOnRemove($photo, $event)
    {
        $album = $photo->getAlbum();
        $album->setPhotoCount($album->getPhotoCount() - 1);
        /**
         * TODO: possibly update the album start and end date after deleting an
         * photo, this would however be a hassle to implement. It probably won't
         * ever occur.
         */
    }

    /**
     * Deletes files associated to the photo.
     *
     * @ORM\PreRemove()
     * @param PhotoModel $photo
     * @param $event
     */
    public function deleteFilesOnRemove(PhotoModel $photo, $event) {

    }
}