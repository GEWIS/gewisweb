<?php

namespace Decision\Model;

use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    JoinColumn,
    ManyToOne,
};
use User\Model\User as UserModel;

/**
 * Organ information.
 */
#[Entity]
class OrganInformation
{
    /**
     * Organ information ID.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected ?int $id;

    /**
     *
     */
    #[ManyToOne(
        targetEntity: Organ::class,
        inversedBy: "organInformation",
    )]
    #[JoinColumn(
        name: "organ_id",
        referencedColumnName: "id",
        nullable: false,
    )]
    protected Organ $organ;

    /**
     * The email address of the organ if available.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $email;

    /**
     * The website of the organ if available.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $website;

    /**
     * A short description of the organ in dutch.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $shortDutchDescription;

    /**
     * A description of the organ in dutch.
     */
    #[Column(
        type: "text",
        nullable: true,
    )]
    protected ?string $dutchDescription;

    /**
     * A short description of the organ in english.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $shortEnglishDescription;

    /**
     * A description of the organ in english.
     */
    #[Column(
        type: "text",
        nullable: true,
    )]
    protected ?string $englishDescription;

    /**
     * The cover photo to display for this organ.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $coverPath;

    /**
     * The thumbnail photo to display for this organ.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $thumbnailPath;

    /**
     * Who was the last one to approve this information. If null then nobody approved it.
     */
    #[ManyToOne(targetEntity: UserModel::class)]
    #[JoinColumn(referencedColumnName: "lidnr")]
    protected ?UserModel $approver;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Organ
     */
    public function getOrgan(): Organ
    {
        return $this->organ;
    }

    /**
     * @param Organ $organ
     */
    public function setOrgan(Organ $organ): void
    {
        $this->organ = $organ;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     */
    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string|null
     */
    public function getWebsite(): ?string
    {
        return $this->website;
    }

    /**
     * @param string|null $website
     */
    public function setWebsite(?string $website): void
    {
        $this->website = $website;
    }

    /**
     * @return string|null
     */
    public function getShortDutchDescription(): ?string
    {
        return $this->shortDutchDescription;
    }

    /**
     * @param string|null $shortDutchDescription
     */
    public function setShortDutchDescription(?string $shortDutchDescription): void
    {
        $this->shortDutchDescription = $shortDutchDescription;
    }

    /**
     * @return string|null
     */
    public function getDutchDescription(): ?string
    {
        return $this->dutchDescription;
    }

    /**
     * @param string|null $dutchDescription
     */
    public function setDutchDescription(?string $dutchDescription): void
    {
        $this->dutchDescription = $dutchDescription;
    }

    /**
     * @return string|null
     */
    public function getShortEnglishDescription(): ?string
    {
        return $this->shortEnglishDescription;
    }

    /**
     * @param string|null $shortEnglishDescription
     */
    public function setShortEnglishDescription(?string $shortEnglishDescription): void
    {
        $this->shortEnglishDescription = $shortEnglishDescription;
    }

    /**
     * @return string|null
     */
    public function getEnglishDescription(): ?string
    {
        return $this->englishDescription;
    }

    /**
     * @param string|null $englishDescription
     */
    public function setEnglishDescription(?string $englishDescription): void
    {
        $this->englishDescription = $englishDescription;
    }

    /**
     * @return UserModel|null
     */
    public function getApprover(): ?UserModel
    {
        return $this->approver;
    }

    /**
     * @param UserModel|null $approver
     */
    public function setApprover(?UserModel $approver): void
    {
        $this->approver = $approver;
    }

    /**
     * @return string|null
     */
    public function getCoverPath(): ?string
    {
        return $this->coverPath;
    }

    /**
     * @param string|null $coverPath
     */
    public function setCoverPath(?string $coverPath): void
    {
        $this->coverPath = $coverPath;
    }

    /**
     * @return string|null
     */
    public function getThumbnailPath(): ?string
    {
        return $this->thumbnailPath;
    }

    /**
     * @param string|null $thumbnailPath
     */
    public function setThumbnailPath(?string $thumbnailPath): void
    {
        $this->thumbnailPath = $thumbnailPath;
    }

    public function __clone()
    {
        $this->id = null;
    }
}
