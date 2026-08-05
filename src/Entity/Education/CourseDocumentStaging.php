<?php

declare(strict_types=1);

namespace App\Entity\Education;

use App\Entity\Application\Enums\Languages;
use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Education\Enums\CourseDocumentTypes;
use App\Entity\Education\Enums\ExamTypes;
use App\Entity\User\User;
use App\Repository\Education\CourseDocumentStagingRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * A PDF that has been uploaded but not yet filed. Its fields mirror what an {@see Exam} or {@see Summary} needs, so
 * publishing is a copy rather than a translation.
 */
#[Entity(repositoryClass: CourseDocumentStagingRepository::class)]
class CourseDocumentStaging
{
    use IdentifiableTrait;

    /** Kept so a row can be recognised when a guess comes out wrong. */
    #[Column(type: Types::STRING)]
    private string $originalFilename;

    /** Carried over to the document on publication rather than copied again. */
    #[Column(type: Types::STRING)]
    private string $path;

    #[ManyToOne(targetEntity: User::class)]
    #[JoinColumn(
        name: 'uploaded_by',
        referencedColumnName: 'lidnr',
        nullable: true,
        onDelete: 'SET NULL',
    )]
    private ?User $uploadedBy = null;

    #[Column(type: Types::DATETIME_MUTABLE)]
    private DateTime $uploadedAt;

    /** Guessed from the filename, so it may be wrong or missing; checked to exist before anything is published. */
    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $courseCode = null;

    #[Column(
        type: Types::DATE_MUTABLE,
        nullable: true,
    )]
    private ?DateTime $date = null;

    #[Column(
        type: Types::STRING,
        enumType: Languages::class,
    )]
    private Languages $language = Languages::English;

    #[Column(
        type: Types::STRING,
        enumType: CourseDocumentTypes::class,
    )]
    private CourseDocumentTypes $type = CourseDocumentTypes::Exam;

    #[Column(
        type: Types::STRING,
        nullable: true,
        enumType: ExamTypes::class,
    )]
    private ?ExamTypes $examType = ExamTypes::Final;

    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $author = null;

    #[Column(type: Types::BOOLEAN)]
    private bool $scanned = false;

    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(string $originalFilename): void
    {
        $this->originalFilename = $originalFilename;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getUploadedBy(): ?User
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(?User $uploadedBy): void
    {
        $this->uploadedBy = $uploadedBy;
    }

    public function getUploadedAt(): DateTime
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(DateTime $uploadedAt): void
    {
        $this->uploadedAt = $uploadedAt;
    }

    public function getCourseCode(): ?string
    {
        return $this->courseCode;
    }

    public function setCourseCode(?string $courseCode): void
    {
        $this->courseCode = $courseCode;
    }

    public function getDate(): ?DateTime
    {
        return $this->date;
    }

    public function setDate(?DateTime $date): void
    {
        $this->date = $date;
    }

    public function getLanguage(): Languages
    {
        return $this->language;
    }

    public function setLanguage(Languages $language): void
    {
        $this->language = $language;
    }

    public function getType(): CourseDocumentTypes
    {
        return $this->type;
    }

    public function setType(CourseDocumentTypes $type): void
    {
        $this->type = $type;
    }

    public function getExamType(): ?ExamTypes
    {
        return $this->examType;
    }

    public function setExamType(?ExamTypes $examType): void
    {
        $this->examType = $examType;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): void
    {
        $this->author = $author;
    }

    public function getScanned(): bool
    {
        return $this->scanned;
    }

    public function setScanned(bool $scanned): void
    {
        $this->scanned = $scanned;
    }
}
