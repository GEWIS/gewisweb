<?php

namespace Photo\Model;

use Doctrine\ORM\Mapping as ORM;
use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * Photo.
 *
 * @ORM\Entity
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
     * @ORM\Column(type="decimal")
     */
    protected $focalLength;
    
    /**
     * The exposure time, in seconds.
     * 
     * @ORM\Column(type="decimal")
     */
    protected $exposureTime;
    
    /**
     * The inverse of the shutter speed.
     * 
     * @ORM\Column(type="smallint")
     */
    protected $shutterSpeed;
    
    /**
     * The lens aperture.
     * 
     * @ORM\Column(type="decimal")
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
    public function getDate()
    {
        return $this->date;
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
     * @return integer
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
     * Set the date.
     *
     * @param \DateTime $date
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;
    }
    
    /**
     * Set the artist.
     * 
     * @param string $artist 
     */
    public function setArtist(string $artist)
    {
        $this->artist = $artist;
    }
    
    /**
     * Set the camera.
     * 
     * @param string $camera
     */
    public function setCamera(string $camera)
    {
        $this->camera = $camera;
    }
    
    /**
     * Set the flash.
     * 
     * @param boolean $flash
     */
    public function setFlash(boolean $flash)
    {
        $this->flash = $flash;
    }
    
    /**
     * Set the focal length.
     * 
     * @param string $focalLength
     */
    public function setFocalLength(string $focalLength)
    {
        $this->focalLength = $focalLength;
    }
    
    /**
     * Set the exposure time.
     * 
     * @param string $exposureTime
     */
    public function setExposureTime(string $exposureTime)
    {
        $this->exposureTime = $exposureTime;
    }    
    
    /**
     * Set the shutter speed.
     * 
     * @param integer $shutterSpeed
     */
    public function setShutterSpeed(integer $shutterSpeed)
    {
        $this->shutterSpeed = $shutterSpeed;
    }
    
    /**
     * Set the aperture.
     * 
     * @param string $aperture
     */
    public function setAperture(string $aperture)
    {
        $this->aperture = $aperture;
    }
    
    /**
     * Set the ISO.
     * 
     * @param integer $iso
     */
    public function setIso(integer $iso)
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
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'photo';
    }

}
