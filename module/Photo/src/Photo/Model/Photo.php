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
     * The GPS longitude of the location where the photo was taken.
     *
     * @ORM\Column(type="float", nullable=true)
     */
    protected $longitude;

    /**
     * The GPS latitude of the location where the photo was taken.
     *
     * @ORM\Column(type="float", nullable=true)
     */
    protected $latitude;

    /**
     * All the hits of this photo.
     * @ORM\OneToMany(targetEntity="Hit", mappedBy="photo", cascade={"persist", "remove"})
     */
    protected $hits;

    /**
     * All the tags for this photo.
     *
     * @ORM\OneToMany(targetEntity="Tag", mappedBy="photo", cascade={"persist", "remove"}, fetch="EAGER")
     */
    protected $tags;

    /**
     * The corresponding WeeklyPhoto entity if this photo has been a weekly photo
     * @ORM\OneToOne(targetEntity="WeeklyPhoto", mappedBy="photo", cascade={"persist", "remove"})
     */
    protected $weeklyPhoto;

    /**
     * The aspect ratio of the photo width/height
     *
     * @ORM\Column(type="float", nullable=true)
     */
    protected $aspectRatio;

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
     * Get the GPS longitude of the location where the photo was taken.
     *
     * @return float
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Get the GPS latitude of the location where the photo was taken.
     *
     * @return float
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @return \Photo\Model\Tag
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @return int
     */
    public function getTagCount()
    {
        return $this->tags->count();
    }

    /**
     * @return int
     */
    public function getHitCount()
    {
        return $this->hits->count();
    }

    /**
     * @return \Photo\Model\WeeklyPhoto|null
     */
    public function getWeeklyPhoto()
    {
        return $this->weeklyPhoto;
    }

    /**
     * @return float
     */
    public function getAspectRatio()
    {
        return $this->aspectRatio;
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

    /**
     * Set the GPS longitude of the location where the photo was taken.
     *
     * @param float $longitude
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
    }

    /**
     * Set the GPS latitude of the location where the photo was taken.
     *
     * @param float $latitude
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
    }

    /**
     * Sets the aspect ratio
     *
     * @param float $ratio
     */
    public function setAspectRatio($ratio)
    {
        $this->aspectRatio = $ratio;
    }

    /**
     * Add a hit to a photo
     *
     * @param \Photo\Model\Hit $hit
     */
    public function addHit($hit)
    {
        $hit->setPhoto($this);
        $this->hits[] = $hit;
    }

    /**
     * Add a tag to a photo.
     *
     * @param \Photo\Model\Tag $tag
     */
    public function addTag($tag)
    {
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
        $array = [
            'id' => $this->getId(),
            'dateTime' => $this->getDateTime(),
            'artist' => $this->getArtist(),
            'camera' => $this->getCamera(),
            'flash' => $this->getFlash(),
            'focalLength' => $this->getFocalLength(),
            'exposureTime' => $this->getExposureTime(),
            'shutterSpeed' => $this->getShutterSpeed(),
            'aperture' => $this->getAperture(),
            'iso' => $this->getIso(),
            'album' => $this->getAlbum()->toArray(),
            'path' => $this->getPath(),
            'smallThumbPath' => $this->getSmallThumbPath(),
            'largeThumbPath' => $this->getLargeThumbPath(),
            'longitude' => $this->getLongitude(),
            'latitude' => $this->getLatitude(),
        ];

        return $array;
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
