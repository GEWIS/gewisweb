<?php

namespace Photo\Model;

use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping as ORM;
use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * Photo.
 *
 * @ORM\Entity
 * @HasLifecycleCallbacks
 */
class Photo implements ResourceInterface
{

    /**
     * Photo ID.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * Date added
     *
     * @ORM\Column(type="date")
     */
    protected $date;
    // TODO: add more metadata here later
    /**
     * Album in which the photo is.
     *
     * @ORM\ManyToOne(targetEntity="Photo\Model\Album", inversedBy="photos")
     * @ORM\JoinColumn(name="album_id",referencedColumnName="id")
     */
    protected $album;

    /**
     * The path where the photo is located relative to the storage directory
     *
     * @ORM\Column(type="string")
     */
    protected $path;

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
     * Get the date.
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Get the album.
     *
     * @return Album
     */
    public function getAlbum()
    {
        return $this->album;
    }

    /**
     * Get the path where the photo is stored.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set the date.
     *
     * @param \DateTime $date
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;
    }

    /**
     * Set the album
     *
     * @param Album $album
     */
    public function setAlbum($album)
    {
        $this->album = $album;
    }

    /**
     * Set the path where the photo is stored
     *
     * @param Album $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }
  
    /** 
     * Updates the photoCount in the album object.
     * 
     * @PrePersist 
     */
    public function IncrementOnPrePersist()
    {
        $this->album->setPhotoCount($this->album->getPhotoCount+1);
    }
    
    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'photo';
    }

}
