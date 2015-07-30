<?php

namespace Photo\Model;

use Doctrine\ORM\Mapping as ORM;
use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * Album.
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 *
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
     * First date of photos in album
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $startDateTime = null;

    /**
     * End date of photos in album
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
     * @ORM\JoinColumn(name="parent_id",referencedColumnName="id")
     */
    protected $parent;

    /**
     * all the subalbums
     * @ORM\OneToMany(targetEntity="Photo\Model\Album", mappedBy="parent", cascade={"persist", "remove"})
     */
    protected $children;

    /**
     * all the photo's in this album.
     * @ORM\OneToMany(targetEntity="Photo", mappedBy="album", cascade={"persist", "remove"})
     */
    protected $photos;

    /**
     * The cover photo to display with the album.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $coverPath;

    /**
     * The amount of photos in this album
     *
     * @ORM\Column(type="integer")
     */
    protected $photoCount = 0;

    /**
     * The amount of subalbums in this album
     *
     * @ORM\Column(type="integer")
     */
    protected $albumCount = 0;

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
     * @return DateTime
     */
    public function getStartDateTime()
    {
        return $this->startDateTime;
    }

    /**
     * Get the end date.
     *
     * @return DateTime
     */
    public function getEndDateTime()
    {
        return $this->endDateTime;
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
     * Get the parent album.
     *
     * @return string $parent
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Gets an array of all child albums
     *
     * @return array
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Get the album cover
     *
     * @return photo
     */
    public function getCoverPath()
    {
        return $this->coverPath;
    }

    /**
     * Get the amount of photos in the album
     *
     * @return integer
     */
    public function getPhotoCount()
    {
        return $this->photoCount;
    }

    /**
     * Get the amount of subalbums in the album
     *
     * @return integer
     */
    public function getAlbumCount()
    {
        return $this->albumCount;
    }

    /**
     * Set the start date.
     *
     * @param \DateTime $startDateTime
     */
    public function setStartDateTime(\DateTime $startDateTime)
    {
        $this->startDateTime = $startDateTime;
    }

    /**
     * Set the end date.
     *
     * @param \DateTime $endDateTime
     */
    public function setEndDateTime(\DateTime $endDateTime)
    {
        $this->endDateTime = $endDateTime;
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
     * Set the parent of the album
     *
     * @param album $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * Set the cover photo for the album
     *
     * @param photo $photo
     */
    public function setCoverPath($photo)
    {
        $this->coverPath = $photo;
    }

    /**
     * Set the amount of photos in an album
     *
     * @param integer $count
     */
    public function setPhotoCount($count)
    {
        $this->photoCount = $count;
    }

    /**
     * Set the amount of subalbums in an album
     *
     * @param integer $count
     */
    public function setAlbumCount($count)
    {
        $this->albumCount = $count;
    }

    /**
     * Returns an associative array representation of this object.
     *
     * @return array
     */
    public function toArray()
    {
        $array = array(
            'id' => $this->id,
            'startDateTime' => $this->startDateTime,
            'endDateTime' => $this->endDateTime,
            'name' => $this->name,
            'parent' => is_null($this->parent) ? null : $this->parent->toArray(),
            'children' => array(),
            'photos' => array(),
            'coverPath' => $this->coverPath,
            'photoCount' => $this->photoCount,
            'albumCount' => $this->albumCount
        );

        return $array;
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
        foreach ($this->children as $album) {
            $array['children'][] = $album->toArray();
        }

        return $array;
    }

    /**
     * Updates the albumCount in the parent album object.
     *
     * @ORM\PrePersist()
     * @ORM\PostUpdate()
     */
    public function updateOnAdd()
    {
        if (!is_null($this->parent) && !is_null($this->getStartDateTime())) {
            $this->parent->setAlbumCount($this->parent->getAlbumCount() + 1);
            if (is_null($this->parent->getStartDateTime()) || $this->parent->getStartDateTime()->getTimestamp() >
                $this->getStartDateTime()->getTimeStamp()
            ) {
                $this->parent->setStartDateTime($this->getStartDateTime());
            }
            if (is_null($this->parent->getEndDateTime()) || $this->parent->getEndDateTime()->getTimestamp() <
                $this->getEndDateTime()->getTimeStamp()
            ) {
                $this->parent->setEndDateTime($this->getEndDateTime());
            }
        }
    }

    /**
     * Updates the albumCount in the parent album object.
     *
     * @ORM\PreRemove()
     * @ORM\PreUpdate()
     */
    public function updateOnRemove()
    {
        if (!is_null($this->parent)) {
            $this->parent->setAlbumCount($this->parent->getAlbumCount() - 1);
        }
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
