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
     * Note: These are fetched extra lazy so we can efficiently retrieve an album count
     * @ORM\OneToMany(targetEntity="Photo\Model\Album", mappedBy="parent", cascade={"persist", "remove"}, fetch="EXTRA_LAZY")
     */
    protected $children;

    /**
     * all the photo's in this album.
     * Note: These are fetched extra lazy so we can efficiently retrieve an photo count
     * @ORM\OneToMany(targetEntity="Photo", mappedBy="album", cascade={"persist", "remove"}, fetch="EXTRA_LAZY")
     */
    protected $photos;

    /**
     * The cover photo to display with the album.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $coverPath;

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
     * @return \DateTime
     */
    public function getStartDateTime()
    {
        return $this->startDateTime;
    }

    /**
     * Get the end date.
     *
     * @return \DateTime
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
        return $this->photos->count();
    }

    /**
     * Get the amount of subalbums in the album
     *
     * @return integer
     */
    public function getAlbumCount()
    {
        return $this->children->count();
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
     * Add a photo to an album.
     *
     * @param \Photo\Model\Photo $photo
     */
    public function addPhoto($photo) {
        $photo->setAlbum($this);
        $this->photos[] = $photo;
    }

    /**
     * Add a sub album to an album.
     *
     * @param \Photo\Model\Album $album
     */
    public function addAlbum($album) {
        $album->setParent($this);
        $this->children[] = $album;
    }

    /**
     * Returns an associative array representation of this object.
     *
     * @return array
     */
    public function toArray()
    {
        $array = array(
            'id' => $this->getId(),
            'startDateTime' => $this->getStartDateTime(),
            'endDateTime' => $this->getEndDateTime(),
            'name' => $this->getName(),
            'parent' => is_null($this->getParent()) ? null : $this->getParent()->toArray(),
            'children' => array(),
            'photos' => array(),
            'coverPath' => $this->getCoverPath(),
            'photoCount' => $this->getPhotoCount(),
            'albumCount' => $this->getAlbumCount()
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
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'album';
    }

}
