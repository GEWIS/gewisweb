<?php

namespace Photo\Model;

use Doctrine\ORM\Mapping as ORM;
use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * Photo.
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * 
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
     * Date and time when the photo was taken.
     *
     * @ORM\Column(type="datetime")
     */
    protected $dateTime;

    /**
     * Artist/author
     * 
     * @ORM\Column(type="string")
     */
    protected $artist;

    /**
     * The type of camera used
     * 
     * @ORM\Column(type="string")
     */
    protected $camera;

    /**
     * Whether a flash has been used
     * 
     * @ORM\Column(type="boolean")
     */
    protected $flash;

    /**
     * The focal length of the lens, in mm.
     * 
     * @ORM\Column(type="float")
     */
    protected $focalLength;

    /**
     * The exposure time, in seconds.
     * 
     * @ORM\Column(type="float")
     */
    protected $exposureTime;

    /**
     * The shutter speed.
     * 
     * @ORM\Column(type="string")
     */
    protected $shutterSpeed;

    /**
     * The lens aperture.
     * 
     * @ORM\Column(type="string")
     */
    protected $aperture;

    /**
     * Indicates the ISO Speed and ISO Latitude of the camera
     * 
     * @ORM\Column(type="smallint")
     */
    protected $iso;

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
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * Get the artist.
     * 
     * @return string 
     */
    public function getArtist()
    {
        return $this->artist;
    }

    /**
     * Get the camera.
     * 
     * @return string 
     */
    public function getCamera()
    {
        return $this->camera;
    }

    /**
     * Get the flash.
     * 
     * @return boolean 
     */
    public function getFlash()
    {
        return $this->flash;
    }

    /**
     * Get the focal length.
     * 
     * @return string 
     */
    public function getFocalLength()
    {
        return $this->focalLength;
    }

    /**
     * Get the exposure time.
     * 
     * @return string 
     */
    public function getExposureTime()
    {
        return $this->exposureTime;
    }

    /**
     * Get the shutter speed.
     * 
     * @return string
     */
    public function getShutterSpeed()
    {
        return $this->shutterSpeed;
    }

    /**
     * Get the aperture.
     * 
     * @return string 
     */
    public function getAperture()
    {
        return $this->aperture;
    }

    /**
     * Get the ISO.
     * 
     * @return integer 
     */
    public function getIso()
    {
        return $this->iso;
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
     * Set the dateTime.
     *
     * @param \DateTime $dateTime
     */
    public function setDateTime(\DateTime $dateTime)
    {
        $this->dateTime = $dateTime;
    }

    /**
     * Set the artist.
     * 
     * @param string $artist 
     */
    public function setArtist($artist)
    {
        $this->artist = $artist;
    }

    /**
     * Set the camera.
     * 
     * @param string $camera
     */
    public function setCamera($camera)
    {
        $this->camera = $camera;
    }

    /**
     * Set the flash.
     * 
     * @param boolean $flash
     */
    public function setFlash($flash)
    {
        $this->flash = $flash;
    }

    /**
     * Set the focal length.
     * 
     * @param string $focalLength
     */
    public function setFocalLength($focalLength)
    {
        $this->focalLength = $focalLength;
    }

    /**
     * Set the exposure time.
     * 
     * @param string $exposureTime
     */
    public function setExposureTime($exposureTime)
    {
        $this->exposureTime = $exposureTime;
    }

    /**
     * Set the shutter speed.
     * 
     * @param string $shutterSpeed
     */
    public function setShutterSpeed($shutterSpeed)
    {
        $this->shutterSpeed = $shutterSpeed;
    }

    /**
     * Set the aperture.
     * 
     * @param string $aperture
     */
    public function setAperture($aperture)
    {
        $this->aperture = $aperture;
    }

    /**
     * Set the ISO.
     * 
     * @param integer $iso
     */
    public function setIso($iso)
    {
        $this->iso = $iso;
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
     * Updates the photoCount and date in the album object.
     * 
     * @ORM\PrePersist()
     * @ORM\PostUpdate() 
     */
    public function updateOnAdd()
    {
        $this->album->setPhotoCount($this->album->getPhotoCount() + 1);
        //update start and end date if the added photo is newere or older
        if (is_null($this->album->getStartDateTime())) {
            $this->album->setStartDateTime($this->getDateTime());
        } else if ($this->album->getStartDateTime()->getTimestamp() > $this->getDateTime()->getTimeStamp()) {
            $this->album->setStartDateTime($this->getDateTime());
        }

        if (is_null($this->album->getEndDateTime())) {
            $this->album->setEndDateTime($this->getDateTime());
        } else if ($this->album->getStartDateTime()->getTimestamp() < $this->getDateTime()->getTimeStamp()) {
            $this->album->setEndDateTime($this->getDateTime());
        }
    }

    /**
     * Updates the photoCount in the album object.
     * 
     * @ORM\PreRemove() 
     * @ORM\PreUpdate()
     */
    public function updateOnRemove()
    {
        $this->album->setPhotoCount($this->album->getPhotoCount() - 1);
        /**
         * TODO: possibly update the album start and end date after deleting an 
         * photo, this would however be a hassle to implement. It probably won't
         * ever occur.
         */
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
