<?php

declare(strict_types=1);

namespace Decision\Model;

use Application\Model\Traits\IdentifiableTrait;
use Decision\Model\Member as MemberModel;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * Organ information.
 */
#[Entity]
class OrganInformation
{
    use IdentifiableTrait;

    #[ManyToOne(
        targetEntity: Organ::class,
        inversedBy: 'organInformation',
    )]
    #[JoinColumn(
        name: 'organ_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected Organ $organ;

    /**
     * The email address of the organ if available.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $email = null;

    /**
     * The website of the organ if available.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $website = null;

    /**
     * A short description of the organ in Dutch.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $shortDutchDescription = null;

    /**
     * A description of the organ in Dutch.
     */
    #[Column(
        type: 'text',
        nullable: true,
    )]
    protected ?string $dutchDescription = null;

    /**
     * A short description of the organ in English.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $shortEnglishDescription = null;

    /**
     * A description of the organ in English.
     */
    #[Column(
        type: 'text',
        nullable: true,
    )]
    protected ?string $englishDescription = null;

    /**
     * The cover photo to display for this organ.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $coverPath = null;

    /**
     * The thumbnail photo to display for this organ.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $thumbnailPath = null;

    /**
     * Who was the last one to approve this information. If null then nobody approved it.
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(referencedColumnName: 'lidnr')]
    protected ?MemberModel $approver = null;

    public function getOrgan(): Organ
    {
        return $this->organ;
    }

    public function setOrgan(Organ $organ): void
    {
        $this->organ = $organ;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): void
    {
        $this->website = $website;
    }

    public function getShortDutchDescription(): ?string
    {
        return $this->shortDutchDescription;
    }

    public function setShortDutchDescription(?string $shortDutchDescription): void
    {
        $this->shortDutchDescription = $shortDutchDescription;
    }

    public function getDutchDescription(): ?string
    {
        return $this->dutchDescription;
    }

    public function setDutchDescription(?string $dutchDescription): void
    {
        $this->dutchDescription = $dutchDescription;
    }

    public function getShortEnglishDescription(): ?string
    {
        return $this->shortEnglishDescription;
    }

    public function setShortEnglishDescription(?string $shortEnglishDescription): void
    {
        $this->shortEnglishDescription = $shortEnglishDescription;
    }

    public function getEnglishDescription(): ?string
    {
        return $this->englishDescription;
    }

    public function setEnglishDescription(?string $englishDescription): void
    {
        $this->englishDescription = $englishDescription;
    }

    public function getApprover(): ?MemberModel
    {
        return $this->approver;
    }

    public function setApprover(?MemberModel $approver): void
    {
        $this->approver = $approver;
    }

    public function getCoverPath(): ?string
    {
        return $this->coverPath;
    }

    public function setCoverPath(?string $coverPath): void
    {
        $this->coverPath = $coverPath;
    }

    public function getThumbnailPath(): ?string
    {
        return $this->thumbnailPath;
    }

    public function setThumbnailPath(?string $thumbnailPath): void
    {
        $this->thumbnailPath = $thumbnailPath;
    }

    public function __clone()
    {
        $this->id = null;
    }
}
