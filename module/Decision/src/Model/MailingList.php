<?php

namespace Decision\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mailing List model.
 *
 * @ORM\Entity
 */
class MailingList
{
    /**
     * Mailman-identifier / name.
     *
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * Dutch description of the mailing list.
     *
     * @ORM\Column(type="text")
     */
    protected $nl_description;

    /**
     * English description of the mailing list.
     *
     * @ORM\Column(type="text")
     */
    protected $en_description;

    /**
     * If the mailing list should be on the form.
     *
     * @ORM\Column(type="boolean")
     */
    protected $onForm;

    /**
     * If members should be subscribed by default.
     *
     * (when it is on the form, that means that the checkbox is checked by default)
     *
     * @ORM\Column(type="boolean")
     */
    protected $defaultSub;

    /**
     * Mailing list members.
     *
     * @ORM\ManyToMany(targetEntity="Member", mappedBy="lists")
     */
    protected $members;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->members = new ArrayCollection();
    }

    /**
     * Get the name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get the english description.
     *
     * @return string
     */
    public function getEnDescription()
    {
        return $this->en_description;
    }

    /**
     * Set the english description.
     *
     * @param string $description
     */
    public function setEnDescription($description)
    {
        $this->en_description = $description;
    }

    /**
     * Get the dutch description.
     *
     * @return string
     */
    public function getNlDescription()
    {
        return $this->nl_description;
    }

    /**
     * Set the dutch description.
     *
     * @param string $description
     */
    public function setNlDescription($description)
    {
        $this->nl_description = $description;
    }

    /**
     * Get the description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->getNlDescription();
    }

    /**
     * Set the description.
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->setNlDescription($description);
    }

    /**
     * Get if it should be on the form.
     *
     * @return bool
     */
    public function getOnForm()
    {
        return $this->onForm;
    }

    /**
     * Set if it should be on the form.
     *
     * @param bool $onForm
     */
    public function setOnForm($onForm)
    {
        $this->onForm = $onForm;
    }

    /**
     * Get if it is a default list.
     *
     * @return bool
     */
    public function getDefaultSub()
    {
        return $this->defaultSub;
    }

    /**
     * Set if it is a default list.
     *
     * @param bool $default
     */
    public function setDefaultSub($default)
    {
        $this->defaultSub = $default;
    }

    /**
     * Get subscribed members.
     *
     * @return ArrayCollection of members
     */
    public function getMembers()
    {
        return $this->members;
    }

    /**
     * Add a member.
     */
    public function addMember(Member $member)
    {
        $this->members[] = $member;
    }
}
