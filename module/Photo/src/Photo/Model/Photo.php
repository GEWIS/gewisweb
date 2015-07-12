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
     * @ORM\Column(type="string", nullable=true)
     */
    protected $artist;

    /**
     * The type of camera used
     *
     * @ORM\Column(type="string", nullable=true))
     */
    protected $camera;

    /**
     * Whether a flash has been used
     *
     * @ORM\Column(type="boolean", nullable=true))
     */
    protected $flash;

    /**
     * The focal length of the lens, in mm.
     *
     * @ORM\Column(type="float", nullable=true))
     */
    protected $focalLength;

    /**
     * The exposure time, in seconds.
     *
     * @ORM\Column(type="float", nullable=true))
     */
    protected $exposureTime;

    /**
     * The shutter speed.
     *
     * @ORM\Column(type="string", nullable=true))
     */
    protected $shutterSpeed;

    /**
     * The lens aperture.
     *
     * @ORM\Column(type="string", nullable=true))
     */
    protected $aperture;

    /**
     * Indicates the ISO Speed and ISO Latitude of the camera
     *
     * @ORM\Column(type="smallint", nullable=true))
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
     * The path where the small thumbnail of the photo is located relative to
     * the storage directory
     *
     * @ORM\Column(type="string")
     */
    protected $smallThumbPath;

    /**
     * The path where the large thumbnail of the photo is located relative to
     * the storage directory
     *
     * @ORM\Column(type="string")
     */
    protected $largeThumbPath;

    /**
     * All the hits of this photo.
     * @ORM\OneToMany(targetEntity="Hit", mappedBy="photo", cascade={"persist", "remove"})
     */
    protected $hits;

    /**
     * All the tags for this photo.
     * @ORM\OneToMany(targetEntity="Tag", mappedBy="photo", cascade={"persist", "remove"})
     */
    protected $tags;

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
     * @return DateTime
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
     * Get the path where the large thumbnail is stored.
     *
     * @return string
     */
    public function getLargeThumbPath()
    {
        return $this->largeThumbPath;
    }

    /**
     * Get the path where the large thumbnail is stored.
     *
     * @return string
     */
    public function getSmallThumbPath()
    {
        return $this->smallThumbPath;
    }

    /**
     * @return \Photo\Model\Tag
     */
    public function getTags()
    {
        return $this->tags;
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
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * Set the path where the large thumbnail is stored
     *
     * @param string $path
     */
    public function setLargeThumbPath($path)
    {
        $this->largeThumbPath = $path;
    }

    /**
     * Set the path where the small thumbnail is stored
     *
     * @param string $path
     */
    public function setSmallThumbPath($path)
    {
        $this->smallThumbPath = $path;
    }

    public function addHit($hit) {
        $hit->setPhoto($this);
        $this->hits[] = $hit;
    }
    /**
     * Add a tag to a photo.
     *
     * @param \Photo\Model\Photo $tag
     */
    public function addTag($tag) {
        $tag->setPhoto($this);
        $this->tags[] = $tag;
    }
    /**
     * Returns an associative array representation of this object
     *
     * @return array
     */
    public function toArray()
    {
        $array = array(
            'id' => $this->id,
            'dateTime' => $this->dateTime,
            'artist' => $this->artist,
            'camera' => $this->camera,
            'flash' => $this->flash,
            'focalLength' => $this->focalLength,
            'exposureTime' => $this->exposureTime,
            'shutterSpeed' => $this->shutterSpeed,
            'aperture' => $this->aperture,
            'iso' => $this->iso,
            'album' => $this->album->toArray(),
            'path' => $this->path,
            'smallThumbPath' => $this->smallThumbPath,
            'largeThumbPath' => $this->largeThumbPath
        );

        return $array;
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
        if (is_null($this->album->getStartDateTime()) || $this->album->getStartDateTime()->getTimestamp() > $this->getDateTime()->getTimeStamp()
        ) {
            $this->album->setStartDateTime($this->getDateTime());
        }

        if (is_null($this->album->getEndDateTime()) || $this->album->getEndDateTime()->getTimestamp() < $this->getDateTime()->getTimeStamp()
        ) {
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
