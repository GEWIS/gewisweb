<?php

namespace Photo\Model;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Album.
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Album implements ResourceInterface
{
    /**
     * Album ID.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * First date of photos in album.
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $startDateTime = null;

    /**
     * End date of photos in album.
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $endDateTime = null;

    /**
     * Name of the album.
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * Parent album, null if there is no parent album.
     *
     * @ORM\ManyToOne(targetEntity="Photo\Model\Album", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    protected $parent;

    /**
     * all the subalbums
     * Note: These are fetched extra lazy so we can efficiently retrieve an
     * album count.
     *
     * @ORM\OneToMany(targetEntity="Photo\Model\Album", mappedBy="parent",
     *     cascade={"persist",
     *     "remove"},
     * fetch="EXTRA_LAZY")
     */
    protected $children;

    /**
     * all the photo's in this album.
     * Note: These are fetched extra lazy so we can efficiently retrieve an
     * photo count.
     *
     * @ORM\OneToMany(targetEntity="Photo", mappedBy="album",
     *     cascade={"persist", "remove"},
     * fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"dateTime": "ASC"})
     */
    protected $photos;

    /**
     * The cover photo to display with the album.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $coverPath;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->photos = new ArrayCollection();
    }

    /**
     * Gets an array of all child albums.
     *
     * @return array
     */
    public function getChildren()
    {
        return $this->children->toArray();
    }

    /**
     * Gets an array of all the photos in this album.
     *
     * @return Collection
     */
    public function getPhotos()
    {
        return $this->photos;
    }

    /**
     * Add a photo to an album.
     *
     * @param Photo $photo
     */
    public function addPhoto($photo)
    {
        $photo->setAlbum($this);
        $this->photos[] = $photo;
    }

    /**
     * Add a sub album to an album.
     *
     * @param Album $album
     */
    public function addAlbum($album)
    {
        $album->setParent($this);
        $this->children[] = $album;
    }

    /**
     * Returns an associative array representation of this object
     * including all child objects.
     *
     * @return array
     */
    public function toArrayWithChildren()
    {
        $array = $this->toArray();
        foreach ($this->photos as $photo) {
            $array['photos'][] = $photo->toArray();
        }
        foreach ($this->children as $album) {
            $array['children'][] = $album->toArray();
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
        return [
            'id' => $this->getId(),
            'startDateTime' => $this->getStartDateTime(),
            'endDateTime' => $this->getEndDateTime(),
            'name' => $this->getName(),
            'parent' => is_null($this->getParent()) ? null
                : $this->getParent()->toArray(),
            'children' => [],
            'photos' => [],
            'coverPath' => $this->getCoverPath(),
            'photoCount' => $this->getPhotoCount(),
            'albumCount' => $this->getAlbumCount(),
        ];
    }

    /**
     * Get the ID.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the start date.
     *
     * @return DateTime|null
     */
    public function getStartDateTime()
    {
        return $this->startDateTime;
    }

    /**
     * Set the start date.
     */
    public function setStartDateTime(DateTime $startDateTime)
    {
        $this->startDateTime = $startDateTime;
    }

    /**
     * Get the end date.
     *
     * @return DateTime|null
     */
    public function getEndDateTime()
    {
        return $this->endDateTime;
    }

    /**
     * Set the end date.
     */
    public function setEndDateTime(DateTime $endDateTime)
    {
        $this->endDateTime = $endDateTime;
    }

    /**
     * Get the album name.
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name of the album.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get the parent album.
     *
     * @return Album $parent
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set the parent of the album.
     *
     * @param Album $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * Get the album cover.
     *
     * @return string
     */
    public function getCoverPath()
    {
        return $this->coverPath;
    }

    /**
     * Set the cover photo for the album.
     *
     * @param string $photo
     */
    public function setCoverPath($photo)
    {
        $this->coverPath = $photo;
    }

    /**
     * Get the amount of photos in the album.
     *
     * @return int
     */
    public function getPhotoCount($includeSubAlbums = true)
    {
        $count = $this->photos->count();
        if ($includeSubAlbums) {
            foreach ($this->children as $album) {
                $count += $album->getPhotoCount();
            }
        }

        return $count;
    }

    /**
     * Get the amount of subalbums in the album.
     *
     * @return int
     */
    public function getAlbumCount()
    {
        return $this->children->count();
    }

    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'album';
    }
}