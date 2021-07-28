<?php

namespace Education\Model;

use DateTime;
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
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Exam.
 */
#[Entity]
#[InheritanceType(value: "SINGLE_TABLE")]
#[DiscriminatorColumn(
    name: "type",
    type: "string",
)]
#[DiscriminatorMap(value:
    [
        "exam" => Exam::class,
        "summary" => Summary::class,
    ]
)]
class Exam implements ResourceInterface
{
    public const EXAM_TYPE_FINAL = 'exam';
    public const EXAM_TYPE_INTERMEDIATE_TEST = 'intermediate';
    public const EXAM_TYPE_ANSWERS = 'answers';
    public const EXAM_TYPE_OTHER = 'other';
    public const EXAM_TYPE_SUMMARY = 'summary';

    public const EXAM_LANGUAGE_ENGLISH = 'en';
    public const EXAM_LANGUAGE_DUTCH = 'nl';

    /**
     * Study ID.
     */
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
     * Filename of the exam.
     */
    #[Column(type: "string")]
    protected string $filename;

    /**
     * Type of exam. One of {exam, intermediate, answers, summary}.
     */
    #[Column(type: "string")]
    protected string $examType;

    /**
     * The language of the exam.
     */
    #[Column(type: "string")]
    protected string $language;

    /**
     * Course belonging to this exam.
     */
    #[ManyToOne(
        targetEntity: Course::class,
        inversedBy: "exams",
    )]
    #[JoinColumn(
        name: "course_code",
        referencedColumnName: "code",
        nullable: false,
    )]
    protected Course $course;

    /**
     * Get the ID.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the date.
     *
     * @return DateTime
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * Get the filename.
     *
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * Get the type.
     *
     * @return string
     */
    public function getExamType(): string
    {
        return $this->examType;
    }

    /**
     * Get the course.
     *
     * @return Course
     */
    public function getCourse(): Course
    {
        return $this->course;
    }

    /**
     * Get the language.
     *
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Set the date.
     *
     * @param DateTime $date
     */
    public function setDate(DateTime $date): void
    {
        $this->date = $date;
    }

    /**
     * Set the type.
     *
     * @param string $examType
     */
    public function setExamType(string $examType): void
    {
        $this->examType = $examType;
    }

    /**
     * Set the filename.
     *
     * @param string $filename
     */
    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    /**
     * Set the language.
     *
     * @param string $language
     */
    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    /**
     * Set the course.
     *
     * @param Course $course
     */
    public function setCourse(Course $course): void
    {
        $this->course = $course;
    }

    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId(): string
    {
        return 'exam';
    }
}
