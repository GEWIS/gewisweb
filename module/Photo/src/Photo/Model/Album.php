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
     * @ORM\Column(type="datetime")
     */
    protected $startDateTime;

    /**
     * End date of photos in album
     *
     * @ORM\Column(type="datetime")
     */
    protected $endDateTime;

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
     * @ORM\OneToMany(targetEntity="Photo\Model\Album", mappedBy="parent")
     */
    protected $children;

    /**
     * all the photo's in this album.
     * @ORM\OneToMany(targetEntity="Photo", mappedBy="album")
     */
    protected $photos;

    /**
     * The cover photo to display with the album.
     * @ORM\OneToOne(targetEntity="Photo")
     * @ORM\JoinColumn(name="cover_id", referencedColumnName="id")
     */
    protected $cover;

    /**
     * The amount of photos in this album
     *
     * @ORM\Column(type="integer")
     */
    protected $photoCount;

    /**
     * The amount of subalbums in this album
     *
     * @ORM\Column(type="integer")
     */
    protected $albumCount;

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
     * Get the album cover
     * 
     * @return photo
     */
    public function getCover()
    {
        return $this->cover;
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
    public function setCover($photo)
    {
        $this->cover = $photo;
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
     * Updates the albumCount in the parent album object.
     * 
     * @ORM\PrePersist()
     * @ORM\PostUpdate() 
     */
    public function incrementOnAdd()
    {
        $this->parent->setAlbumCount($this->parent->getAlbumCount() + 1);
    }

    /**
     * Updates the albumCount in the parent album object.
     * 
     * @ORM\PreRemove() 
     * @ORM\PreUpdate()
     */
    public function decrementOnRemove()
    {
        $this->parent->setAlbumCount($this->parent->getAlbumCount() - 1);
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
