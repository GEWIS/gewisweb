<?php

namespace Photo\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Exception;

/**
 * VirtualAlbum.
 * Album that will never be stored in the database as such.
 */
class VirtualAlbum extends Album
{
    public function __construct($id)
    {
        parent::__construct();
        $this->id = $id;
    }

    /**
     * Get the parent album.
     *
     * @return Album $parent
     */
    public function getParent()
    {
        return null;
    }

    /**
     * Set the parent of the album
     *
     * @param album $parent
     *
     * @throws Exception
     */
    public function setParent($parent)
    {
        throw new Exception("Method is not implemented");
    }

    /**
     * Gets an array of all child albums
     *
     * @return array
     */
    public function getChildren()
    {
        return [];
    }

    public function getPhotos()
    {
        return $this->photos->toArray();
    }

    /**
     * Add a photo to an album.
     *
     * @param Photo $photo
     */
    public function addPhoto($photo)
    {
        $this->photos[] = $photo;
    }

    public function addPhotos(array $photos)
    {
        $this->photos
            = new ArrayCollection(
                array_merge(
                    $this->photos->toArray(),
                    $photos
                )
            );
    }

    /**
     * Add a sub album to an album.
     *
     * @param Album $album
     *
     * @throws Exception
     */
    public function addAlbum($album)
    {
        throw new Exception("Method is not implemented");
    }

    /**
     * Returns an associative array representation of this object
     * including all child objects
     *
     * @return array
     */
    function toArrayWithChildren()
    {
        $array = $this->toArray();
        foreach ($this->photos as $photo) {
            $array['photos'][] = $photo->toArray();
        }
        // TODO: The code below probably never was finished
        foreach ($this->children as $album) {
            $array['children'][] = [];
        }

        return $array;
    }

    /**
     * Returns an associative array representation of this object.
     *
     * @return array
     */
    public function toArray()
    {
        $array = [
            'id'            => $this->getId(),
            'startDateTime' => $this->getStartDateTime(),
            'endDateTime'   => $this->getEndDateTime(),
            'name'          => $this->getName(),
            'parent'        => null,
            'children'      => [],
            'photos'        => [],
            'coverPath'     => $this->getCoverPath(),
            'photoCount'    => $this->getPhotoCount(),
            'albumCount'    => $this->getAlbumCount()
        ];

        return $array;
    }

    /**
     * Get the amount of photos in the album
     *
     * @return integer
     */
    public function getPhotoCount($includeSubAlbums = false)
    {
        $count = $this->photos->count();
        return $count;
    }

    /**
     * Get the amount of subalbums in the album
     *
     * @return integer
     */
    public function getAlbumCount()
    {
        return 0;
    }
}
