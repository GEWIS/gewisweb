<?php

namespace Photo\Model;

use Doctrine\ORM\Mapping as ORM;
use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * Hit, represents a hit for a photo.
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 *
 */
class Hit implements ResourceInterface
{

    /**
     * Hit ID.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * Date and time when the photo was viewed.
     *
     * @ORM\Column(type="datetime")
     */
    protected $dateTime;

    /**
     * The photo which was viewed.
     *
     * @ORM\ManyToOne(targetEntity="Photo\Model\Photo", inversedBy="hits")
     * @ORM\JoinColumn(name="photo_id", referencedColumnName="id")
     */
    protected $photo;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * @return \Photo\Model\Photo
     */
    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param \DateTime $dateTime
     */
    public function setDateTime($dateTime)
    {
        $this->dateTime = $dateTime;
    }

    /**
     * @param \Photo\Model\Photo $photo
     */
    public function setPhoto($photo)
    {
        $this->photo = $photo;
    }

    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'hit';
    }
}