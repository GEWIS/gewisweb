<?php

namespace Photo\Model;

/**
 * VirtualAlbum.
 * Album that will never be stored in the database as such.
 */
class VirtualAlbum extends Album
{
    /**
     * Album ID.
     *
     */
    protected $id;
    
    /**
     * First date of photos in album
     */
    protected $startDateTime = null;
    
    /**
     * End date of photos in album
     */
    protected $endDateTime = null;
    
    /**
     * Name of the album.
     */
    protected $name;
    
    /**
     * Parent album, null if there is no parent album.
     */
    protected $parent;
    
    /**
     * all the subalbums
     */
    protected $children;
    
    /**
     * all the photo's in this album.
     */
    protected $photos;
    
    /**
     * The cover photo to display with the album.
     */
    protected $coverPath;
    
    public function __construct($id)
    {
        parent::__construct();
        $this->id = $id;
    }
    
    /**
     * Get the parent album.
     *
     * @return \Photo\Model\Album $parent
     */
    public function getParent()
    {
        return null;
    }
    
    /**
     * Set the parent of the album
     *
     * @param album $parent
     *
     * @throws \Exception
     */
    public function setParent($parent)
    {
        throw new \Exception("Method is not implemented");
    }
    
    /**
     * Gets an array of all child albums
     *
     * @return array
     */
    public function getChildren()
    {
        return [];
    }
    
    public function getPhotos()
    {
        return $this->photos->toArray();
    }
    
    /**
     * Add a photo to an album.
     *
     * @param \Photo\Model\Photo $photo
     */
    public function addPhoto($photo)
    {
        $this->photos[] = $photo;
    }
    
    public function addPhotos(array $photos)
    {
        $this->photos
            = new \Doctrine\Common\Collections\ArrayCollection(array_merge($this->photos->toArray(),
            $photos));
    }
    
    /**
     * Add a sub album to an album.
     *
     * @param \Photo\Model\Album $album
     *
     * @throws \Exception
     */
    public function addAlbum($album)
    {
        throw new \Exception("Method is not implemented");
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
            $array['children'][] = [];
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
        $array = [
            'id'            => $this->getId(),
            'startDateTime' => $this->getStartDateTime(),
            'endDateTime'   => $this->getEndDateTime(),
            'name'          => $this->getName(),
            'parent'        => null,
            'children'      => [],
            'photos'        => [],
            'coverPath'     => $this->getCoverPath(),
            'photoCount'    => $this->getPhotoCount(),
            'albumCount'    => $this->getAlbumCount()
        ];
        
        return $array;
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
     * @return \DateTime
     */
    public function getStartDateTime()
    {
        return $this->startDateTime;
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
     * Get the end date.
     *
     * @return \DateTime
     */
    public function getEndDateTime()
    {
        return $this->endDateTime;
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
     * Get the album cover
     *
     * @return string
     */
    public function getCoverPath()
    {
        return "";
    }
    
    /**
     * Set the cover photo for the album
     *
     * @param string $photo
     */
    public function setCoverPath($photo)
    {
        $this->coverPath = $photo;
    }
    
    /**
     * Get the amount of photos in the album
     *
     * @return integer
     */
    public function getPhotoCount($includeSubAlbums = false)
    {
        $count = $this->photos->count();
        
        return $count;
    }
    
    /**
     * Get the amount of subalbums in the album
     *
     * @return integer
     */
    public function getAlbumCount()
    {
        return 0;
    }
    
}
