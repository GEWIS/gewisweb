<?php

declare(strict_types=1);

namespace Education\Model;

use Application\Model\Enums\Languages;
use Application\Model\Traits\IdentifiableTrait;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * @psalm-import-type CourseGdprArrayType from Course as ImportedCourseGdprArrayType
 * @psalm-type CourseDocumentGdprArrayType = array{
 *     id: int,
 *     course: ImportedCourseGdprArrayType,
 *     date: string,
 *     language: string,
 *     scanned: bool,
 *     path: string,
 * }
 */
#[Entity]
#[InheritanceType(value: 'SINGLE_TABLE')]
#[DiscriminatorColumn(
    name: 'type',
    type: 'string',
)]
#[DiscriminatorMap(
    value: [
        'exam' => Exam::class,
        'summary' => Summary::class,
    ],
)]
abstract class CourseDocument implements ResourceInterface
{
    use IdentifiableTrait;

    /**
     * Date of the exam.
     */
    #[Column(type: 'date')]
    protected DateTime $date;

    /**
     * The language of the exam.
     */
    #[Column(
        type: 'string',
        enumType: Languages::class,
    )]
    protected Languages $language;

    /**
     * Filename of the exam.
     */
    #[Column(type: 'string')]
    protected string $filename;

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
    protected Course $course;

    /**
     * Whether the uploaded document is scanned or not. This influences the quality of the watermarking service.
     */
    #[Column(type: 'boolean')]
    protected bool $scanned;

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
     * Get the filename.
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * Set the filename.
     */
    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
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
            'path' => $this->getFilename(),
        ];
    }

    public function getResourceId(): string
    {
        return 'course_document';
    }
}
