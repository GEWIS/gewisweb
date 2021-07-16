<?php

namespace Photo\Model;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Maintains a list of the "Foto of the week".
 *
 * @ORM\Entity
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
     * The start date of the week the photo is based on.
     *
     * @ORM\Column(type="date")
     */
    protected $week;

    /**
     * The photo of the week.
     *
     * @ORM\OneToOne(targetEntity="Photo\Model\Photo", inversedBy="weeklyPhoto")
     * @ORM\JoinColumn(name="photo_id", referencedColumnName="id")
     */
    protected $photo;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return DateTime
     */
    public function getWeek()
    {
        return $this->week;
    }

    /**
     * @return Photo
     */
    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param DateTime $week
     */
    public function setWeek($week)
    {
        $this->week = $week;
    }

    /**
     * @param Photo $photo
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
