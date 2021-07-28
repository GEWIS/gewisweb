<?php

namespace Photo\Model;

use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection,
};
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
     * @return Album|null $parent
     */
    public function getParent(): ?Album
    {
        return null;
    }

    /**
     * Set the parent of the album.
     *
     * @param Album $parent
     *
     * @throws Exception
     */
    public function setParent(Album $parent): void
    {
        throw new Exception('Method is not implemented');
    }

    /**
     * Gets an array of all child albums.
     *
     * @return Collection
     */
    public function getChildren(): Collection
    {
        return new ArrayCollection();
    }

    /**
     * @return Collection
     */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    /**
     * Add a photo to an album.
     *
     * @param Photo $photo
     */
    public function addPhoto(Photo $photo): void
    {
        $this->photos[] = $photo;
    }

    /**
     * @param array $photos
     */
    public function addPhotos(array $photos): void
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
    public function addAlbum(Album $album): void
    {
        throw new Exception('Method is not implemented');
    }

    /**
     * Returns an associative array representation of this object
     * including all child objects.
     *
     * @return array
     */
    public function toArrayWithChildren(): array
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
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'startDateTime' => $this->getStartDateTime(),
            'endDateTime' => $this->getEndDateTime(),
            'name' => $this->getName(),
            'parent' => null,
            'children' => [],
            'photos' => [],
            'coverPath' => $this->getCoverPath(),
            'photoCount' => $this->getPhotoCount(),
            'albumCount' => $this->getAlbumCount(),
        ];
    }

    /**
     * Get the amount of photos in the album.
     *
     * @param bool $includeSubAlbums
     *
     * @return int
     */
    public function getPhotoCount(bool $includeSubAlbums = false): int
    {
        return $this->photos->count();
    }

    /**
     * Get the amount of subalbums in the album.
     *
     * @return int
     */
    public function getAlbumCount(): int
    {
        return 0;
    }
}
