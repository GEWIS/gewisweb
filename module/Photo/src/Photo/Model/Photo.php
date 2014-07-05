<?php
namespace Photo\Model;

use Doctrine\ORM\Mapping as ORM;
use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * Photo.
 *
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * TODO: does this need a discriminator map?
 */
class Photo implements ResourceInterface {

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
    //add more metadata here later
    /**
     * Album in which the photo is.
     *
     * @ORM\ManyToOne(targetEntity="Photo\Model\Album", inversedBy="photos")
     * @ORM\JoinColumn(name="album_id",referencedColumnName="id")
     */
    protected $album;

    /**
     * Get the ID.
     *
     * @return int
     */
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
     * Get the album.
     *
     * @return Album
     */
    public function getAlbum() {
        return $this->album;
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
     * Set the album, this moves the photo another folder .
     *
     * @param Album $album
     */
    public function setAlbum($album) {
        $this->album = $album;
        //TODO: move the actualy photo file to another album
    }

    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId() {
        return 'photo';
    }

}
