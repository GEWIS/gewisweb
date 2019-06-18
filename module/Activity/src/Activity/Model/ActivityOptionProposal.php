<?php

namespace Activity\Model;

use Decision\Model\Organ;
use Doctrine\ORM\Mapping as ORM;
use User\Permissions\Resource\OrganResourceInterface;

/**
 * Activity calendar activity option proposal model.
 *
 * @ORM\Entity
 */
class ActivityOptionProposal implements OrganResourceInterface
{
    /**
     * ID for the option.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * Name for the activity option proposal.
     *
     * @Orm\Column(type="string")
     */
    protected $name;

    /**
     * Description for the activity option proposal.
     *
     * @Orm\Column(type="string")
     */
    protected $description;

    /**
     * Who created this activity option.
     *
     * @ORM\ManyToOne(targetEntity="User\Model\User")
     * @ORM\JoinColumn(referencedColumnName="lidnr",nullable=false)
     */
    protected $creator;

    /**
     * The date and time the activity option was created.
     *
     * @ORM\Column(type="datetime")
     */
    protected $creationTime;

    /**
     * Who created this activity proposal
     *
     * @ORM\ManyToOne(targetEntity="Decision\Model\Organ")
     * @ORM\JoinColumn(referencedColumnName="id",nullable=true)
     */
    protected $organ;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->name;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @param mixed $creator
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;
    }

    /**
     * @return mixed
     */
    public function getCreationTime()
    {
        return $this->creationTime;
    }

    /**
     * @param mixed $creationTime
     */
    public function setCreationTime($creationTime)
    {
        $this->creationTime = $creationTime;
    }

    /**
     * @return mixed
     */
    public function getOrgan()
    {
        return $this->organ;
    }

    /**
     * @param mixed $organ
     */
    public function setOrgan($organ)
    {
        $this->organ = $organ;
    }

    /**
     * Get the organ of this resource.
     *
     * @return Organ
     */
    public function getResourceOrgan()
    {
        return $this->getOrgan();
    }

    /**
     * Returns the string identifier of the Resource
     *
     * @return string
     */
    public function getResourceId()
    {
        return $this->getId();
    }
}
