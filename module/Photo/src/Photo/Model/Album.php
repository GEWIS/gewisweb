<?php

namespace Photo\Model;

use Doctrine\ORM\Mapping as ORM;
use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * Album.
 *
 * @ORM\Entity
 * 
 */
class Album implements ResourceInterface {

    /**
     * Album ID.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * Date album created
     *
     * @ORM\Column(type="date")
     */
    protected $date;

    /**
     * Name of the album.
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * Parent album, null if there is no parent album.
     *
     * @ORM\ManyToOne(targetEntity="Photo\Model\Album", inversedBy="photos")
     * @ORM\JoinColumn(name="parent_id",referencedColumnName="id")
     */
    protected $parent;

    /**
     * all the photo's in this album
     * @ORM\OneToMany(targetEntity="Photo", mappedBy="album")
     */
    protected $photos;

    /**
     * all the subalbums
     * @ORM\OneToMany(targetEntity="Photo\Model\Album", mappedBy="album")
     * 
     */
    protected $children;
    /**
     * Get the ID.
     *
     * @return int
     */

    /**
     *
     * The cover photo to display with the album
     * @ORM\OneToOne(targetEntity="Photo")
     * @ORM\JoinColumn(name="cover_id", referencedColumnName="id")
     */
    protected $cover;

    public function getId() {
        return $this->id;
    }

    /**
     * Get the date.
     *
     * @return \DateTime
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * Get the album name.
     *
     * @return string $name
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Get the album cover
     * 
     * @return photo
     */
    public function getCover() {
        return $this->cover;
    }

    /**
     * Set the date.
     *
     * @param \DateTime $date
     */
    public function setDate(\DateTime $date) {
        $this->date = $date;
    }

    /**
     * Set the name of the album.
     *
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * Set the parent of the album
     * 
     * @param album $parent
     */
    public function setParent($parent) {
        //TODO: actually move the album
        $this->parent = $parent;
    }

    /**
     * Set the cover photo for the album
     * 
     * @param photo $photo
     */
    public function setCover($photo) {
        $this->cover = $cover;
    }

    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId() {
        return 'album';
    }

}
