<?php

declare(strict_types=1);

namespace App\Entity\Decision;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Decision\Member as MemberModel;
use App\Repository\Decision\OrganInformationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * Organ information.
 */
#[Entity(repositoryClass: OrganInformationRepository::class)]
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
    private Organ $organ;

    /**
     * The email address of the organ if available.
     */
    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $email = null;

    /**
     * The website of the organ if available.
     */
    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $website = null;

    /**
     * A short description of the organ in Dutch.
     */
    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $shortDutchDescription = null;

    /**
     * A description of the organ in Dutch.
     */
    #[Column(
        type: Types::TEXT,
        nullable: true,
    )]
    private ?string $dutchDescription = null;

    /**
     * A short description of the organ in English.
     */
    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $shortEnglishDescription = null;

    /**
     * A description of the organ in English.
     */
    #[Column(
        type: Types::TEXT,
        nullable: true,
    )]
    private ?string $englishDescription = null;

    /**
     * The cover photo to display for this organ.
     */
    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $coverPath = null;

    /**
     * The thumbnail photo to display for this organ.
     */
    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $thumbnailPath = null;

    /**
     * Who was the last one to approve this information. If null then nobody approved it.
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(referencedColumnName: 'lidnr')]
    private ?MemberModel $approver = null;

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
