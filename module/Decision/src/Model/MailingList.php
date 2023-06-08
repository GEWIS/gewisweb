<?php

declare(strict_types=1);

namespace Decision\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;

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
    #[Column(type: 'string')]
    protected string $name;

    /**
     * Dutch description of the mailing list.
     */
    #[Column(type: 'text')]
    protected string $nl_description;

    /**
     * English description of the mailing list.
     */
    #[Column(type: 'text')]
    protected string $en_description;

    /**
     * If the mailing list should be on the form.
     */
    #[Column(type: 'boolean')]
    protected bool $onForm;

    /**
     * If members should be subscribed by default.
     *
     * (when it is on the form, that means that the checkbox is checked by default)
     */
    #[Column(type: 'boolean')]
    protected bool $defaultSub;

    /**
     * Mailing list members.
     *
     * @var Collection<Member>
     */
    #[ManyToMany(
        targetEntity: Member::class,
        mappedBy: 'lists',
    )]
    protected Collection $members;

    public function __construct()
    {
        $this->members = new ArrayCollection();
    }

    /**
     * Get the name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the english description.
     */
    public function getEnDescription(): string
    {
        return $this->en_description;
    }

    /**
     * Set the english description.
     */
    public function setEnDescription(string $description): void
    {
        $this->en_description = $description;
    }

    /**
     * Get the dutch description.
     */
    public function getNlDescription(): string
    {
        return $this->nl_description;
    }

    /**
     * Set the dutch description.
     */
    public function setNlDescription(string $description): void
    {
        $this->nl_description = $description;
    }

    /**
     * Get the description.
     */
    public function getDescription(): string
    {
        return $this->getNlDescription();
    }

    /**
     * Set the description.
     */
    public function setDescription(string $description): void
    {
        $this->setNlDescription($description);
    }

    /**
     * Get if it should be on the form.
     */
    public function getOnForm(): bool
    {
        return $this->onForm;
    }

    /**
     * Set if it should be on the form.
     */
    public function setOnForm(bool $onForm): void
    {
        $this->onForm = $onForm;
    }

    /**
     * Get if it is a default list.
     */
    public function getDefaultSub(): bool
    {
        return $this->defaultSub;
    }

    /**
     * Set if it is a default list.
     */
    public function setDefaultSub(bool $default): void
    {
        $this->defaultSub = $default;
    }

    /**
     * Get subscribed members.
     *
     * @return Collection<Member>
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
