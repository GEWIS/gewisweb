<?php

namespace Decision\Model;

use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection,
};
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    Id,
    ManyToMany,
};

/**
 * Mailing List model.
 */
#[Entity]
class MailingList
{
    /**
     * Mailman-identifier / name.
     */
    #[Id]
    #[Column(type: "string")]
    protected string $name;

    /**
     * Dutch description of the mailing list.
     */
    #[Column(type: "text")]
    protected string $nl_description;

    /**
     * English description of the mailing list.
     */
    #[Column(type: "text")]
    protected string $en_description;

    /**
     * If the mailing list should be on the form.
     */
    #[Column(type: "boolean")]
    protected bool $onForm;

    /**
     * If members should be subscribed by default.
     *
     * (when it is on the form, that means that the checkbox is checked by default)
     */
    #[Column(type: "boolean")]
    protected bool $defaultSub;

    /**
     * Mailing list members.
     */
    #[ManyToMany(
        targetEntity: "Decision\Model\Member",
        mappedBy: "lists",
    )]
    protected Collection $members;

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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name.
     *
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the english description.
     *
     * @return string
     */
    public function getEnDescription(): string
    {
        return $this->en_description;
    }

    /**
     * Set the english description.
     *
     * @param string $description
     */
    public function setEnDescription(string $description): void
    {
        $this->en_description = $description;
    }

    /**
     * Get the dutch description.
     *
     * @return string
     */
    public function getNlDescription(): string
    {
        return $this->nl_description;
    }

    /**
     * Set the dutch description.
     *
     * @param string $description
     */
    public function setNlDescription(string $description): void
    {
        $this->nl_description = $description;
    }

    /**
     * Get the description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->getNlDescription();
    }

    /**
     * Set the description.
     *
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->setNlDescription($description);
    }

    /**
     * Get if it should be on the form.
     *
     * @return bool
     */
    public function getOnForm(): bool
    {
        return $this->onForm;
    }

    /**
     * Set if it should be on the form.
     *
     * @param bool $onForm
     */
    public function setOnForm(bool $onForm): void
    {
        $this->onForm = $onForm;
    }

    /**
     * Get if it is a default list.
     *
     * @return bool
     */
    public function getDefaultSub(): bool
    {
        return $this->defaultSub;
    }

    /**
     * Set if it is a default list.
     *
     * @param bool $default
     */
    public function setDefaultSub(bool $default): void
    {
        $this->defaultSub = $default;
    }

    /**
     * Get subscribed members.
     *
     * @return Collection of members
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    /**
     * Add a member.
     */
    public function addMember(Member $member): void
    {
        $this->members[] = $member;
    }
}
