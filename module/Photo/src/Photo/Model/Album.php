<?php
namespace Photo\Model;

use Doctrine\ORM\Mapping as ORM;
use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * Album.
 *
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * TODO: does this need a discriminator map?
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
     * Get the album name.
     *
     * @return string $name
     */
    public function getName() {
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
     * Set the name of the album.
     *
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
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
