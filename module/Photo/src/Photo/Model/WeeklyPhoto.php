<?php

namespace Photo\Model;

use Doctrine\ORM\Mapping as ORM;
use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * Maintains a list of the "Foto of the week"
 *
 * @ORM\Entity
 * 
 */
class WeeklyPhoto implements ResourceInterface
{

    /**
     * Week Id.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * A date in the week the photo belongs to.
     *
     * @ORM\Column(type="datetime")
     */
    protected $week;

    /**
     * The photo of the week.
     *
     * @ORM\ManyToOne(targetEntity="Photo\Model\Photo", inversedBy="week")
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
    public function getWeek()
    {
        return $this->week;
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
     * @param \DateTime $week
     */
    public function setWeek($week)
    {
        $this->dateTime = $week;
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
        return 'weeklyphoto';
    }
}