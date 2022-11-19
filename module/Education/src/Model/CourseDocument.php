<?php

namespace Education\Model;

use Application\Model\Enums\Languages;
use Doctrine\ORM\Mapping\{
    Column,
    DiscriminatorColumn,
    DiscriminatorMap,
    Entity,
    GeneratedValue,
    Id,
    InheritanceType,
    JoinColumn,
    ManyToOne,
};
use DateTime;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

#[Entity]
#[InheritanceType(value: "SINGLE_TABLE")]
#[DiscriminatorColumn(
    name: "type",
    type: "string",
)]
#[DiscriminatorMap(
    value: [
        "exam" => Exam::class,
        "summary" => Summary::class,
    ],
)]
abstract class CourseDocument implements ResourceInterface
{
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected ?int $id = null;

    /**
     * Date of the exam.
     */
    #[Column(type: "date")]
    protected DateTime $date;

    /**
     * The language of the exam.
     */
    #[Column(
        type: "string",
        enumType: Languages::class,
    )]
    protected Languages $language;

    /**
     * Filename of the exam.
     */
    #[Column(type: "string")]
    protected string $filename;

    /**
     * The course to which this document belongs.
     */
    #[ManyToOne(
        targetEntity: Course::class,
        inversedBy: "documents",
    )]
    #[JoinColumn(
        name: "course_code",
        referencedColumnName: "code",
        nullable: false,
    )]
    protected Course $course;

    /**
     * Get the ID.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

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
     * @inheritDoc
     */
    public function getResourceId(): string
    {
        return 'course_document';
    }
}
