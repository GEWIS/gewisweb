<?php

declare(strict_types=1);

namespace App\Entity\Education;

use App\Entity\Application\Enums\Languages;
use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Education\Enums\CourseDocumentTypes;
use App\Entity\Education\Enums\DocumentFlattenStatus;
use App\Repository\Education\CourseDocumentRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OrderBy;

/**
 * @phpstan-import-type CourseGdprArrayType from Course as ImportedCourseGdprArrayType
 * @phpstan-type CourseDocumentGdprArrayType = array{
 *     id: ?int,
 *     course: ImportedCourseGdprArrayType,
 *     date: string,
 *     language: string,
 *     scanned: bool,
 *     path: string,
 * }
 */
#[Entity(repositoryClass: CourseDocumentRepository::class)]
#[InheritanceType(value: 'SINGLE_TABLE')]
#[DiscriminatorColumn(
    name: 'type',
    type: Types::STRING,
)]
#[DiscriminatorMap(
    value: [
        'exam' => Exam::class,
        'summary' => Summary::class,
    ],
)]
abstract class CourseDocument
{
    use IdentifiableTrait;

    /**
     * Date of the exam.
     */
    #[Column(type: Types::DATE_MUTABLE)]
    private DateTime $date;

    /**
     * The language of the exam.
     */
    #[Column(
        type: Types::STRING,
        enumType: Languages::class,
    )]
    private Languages $language;

    /**
     * The stored path of the uploaded PDF. It is never served: downloads are rebuilt from {@see $pages}.
     */
    #[Column(type: Types::STRING)]
    private string $path;

    /**
     * The rendered pages a download is rebuilt from, in page order.
     *
     * @var Collection<array-key, CourseDocumentPage>
     */
    #[OneToMany(
        targetEntity: CourseDocumentPage::class,
        mappedBy: 'document',
        cascade: ['persist'],
    )]
    #[OrderBy(value: ['pageNumber' => 'ASC'])]
    private Collection $pages;

    #[Column(
        type: Types::STRING,
        enumType: DocumentFlattenStatus::class,
    )]
    private DocumentFlattenStatus $flattenStatus = DocumentFlattenStatus::Pending;

    #[Column(
        type: Types::DATETIME_MUTABLE,
        nullable: true,
    )]
    private ?DateTime $flattenedAt = null;

    /**
     * Why rasterization failed, kept so an administrator can tell a corrupt upload from a missing binary.
     */
    #[Column(
        type: Types::TEXT,
        nullable: true,
    )]
    private ?string $flattenError = null;

    /**
     * The course to which this document belongs.
     */
    #[ManyToOne(
        targetEntity: Course::class,
        inversedBy: 'documents',
    )]
    #[JoinColumn(
        name: 'course_code',
        referencedColumnName: 'code',
        nullable: false,
    )]
    private Course $course;

    /**
     * Whether the uploaded document is scanned or not. This influences the quality of the watermarking service.
     */
    #[Column(type: Types::BOOLEAN)]
    private bool $scanned;

    public function __construct()
    {
        $this->pages = new ArrayCollection();
    }

    /**
     * Which kind of material this is. Mirrors the discriminator, so a template can branch on it without knowing which
     * subclass it has.
     */
    abstract public function getType(): CourseDocumentTypes;

    /**
     * Get the date.
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * Set the date.
     */
    public function setDate(DateTime $date): void
    {
        $this->date = $date;
    }

    /**
     * Get the language.
     */
    public function getLanguage(): Languages
    {
        return $this->language;
    }

    /**
     * Set the language.
     */
    public function setLanguage(Languages $language): void
    {
        $this->language = $language;
    }

    /**
     * Get the stored path of the uploaded PDF.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set the stored path of the uploaded PDF.
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * @return Collection<array-key, CourseDocumentPage>
     */
    public function getPages(): Collection
    {
        return $this->pages;
    }

    public function addPage(CourseDocumentPage $page): void
    {
        if ($this->pages->contains($page)) {
            return;
        }

        $page->setDocument($this);
        $this->pages->add($page);
    }

    public function clearPages(): void
    {
        $this->pages->clear();
    }

    public function getPageCount(): int
    {
        return $this->pages->count();
    }

    public function getFlattenStatus(): DocumentFlattenStatus
    {
        return $this->flattenStatus;
    }

    public function setFlattenStatus(DocumentFlattenStatus $flattenStatus): void
    {
        $this->flattenStatus = $flattenStatus;
    }

    public function getFlattenedAt(): ?DateTime
    {
        return $this->flattenedAt;
    }

    public function setFlattenedAt(?DateTime $flattenedAt): void
    {
        $this->flattenedAt = $flattenedAt;
    }

    public function getFlattenError(): ?string
    {
        return $this->flattenError;
    }

    public function setFlattenError(?string $flattenError): void
    {
        $this->flattenError = $flattenError;
    }

    /**
     * Whether the document can be downloaded: it has been rasterized, so there is something to rebuild from.
     */
    public function isDownloadable(): bool
    {
        return DocumentFlattenStatus::Ready === $this->flattenStatus
            && !$this->pages->isEmpty();
    }

    /**
     * Get the course.
     */
    public function getCourse(): Course
    {
        return $this->course;
    }

    /**
     * Set the course.
     */
    public function setCourse(Course $course): void
    {
        $this->course = $course;
    }

    /**
     * Get whether the document is scanned or not.
     */
    public function getScanned(): bool
    {
        return $this->scanned;
    }

    /**
     * Set whether the document is scanned or not.
     */
    public function setScanned(bool $scanned): void
    {
        $this->scanned = $scanned;
    }

    /**
     * @return CourseDocumentGdprArrayType
     */
    public function toGdprArray(): array
    {
        return [
            'id' => $this->getId(),
            'course' => $this->getCourse()->toGdprArray(),
            'date' => $this->getDate()->format(DateTimeInterface::ATOM),
            'language' => $this->getLanguage()->value,
            'scanned' => $this->getScanned(),
            'path' => $this->getPath(),
        ];
    }
}
