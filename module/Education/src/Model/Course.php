<?php

namespace Education\Model;

use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection,
};
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    Id,
    JoinColumn,
    ManyToOne,
    OneToMany,
};
use InvalidArgumentException;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Course.
 */
#[Entity]
class Course implements ResourceInterface
{
    public const QUARTILE_Q1 = 'q1';
    public const QUARTILE_Q2 = 'q2';
    public const QUARTILE_Q3 = 'q3';
    public const QUARTILE_Q4 = 'q4';
    public const QUARTILE_INTERIM = 'interim';

    /**
     * Course code.
     */
    #[Id]
    #[Column(type: "string")]
    protected string $code;

    /**
     * Course name.
     */
    #[Column(type: "string")]
    protected string $name;

    /**
     * Course url.
     */
    #[Column(type: "string")]
    protected string $url;

    /**
     * Last year the course has been given.
     */
    #[Column(type: "integer")]
    protected int $year;

    /**
     * Quartile in which this course has been given.
     *
     * This is an enum. With the following possible values:
     *
     * - q1
     * - q2
     * - q3
     * - q4
     * - interim
     */
    #[Column(type: "string")]
    protected string $quartile;

    /**
     * Exams (and summaries) in this course.
     */
    #[OneToMany(
        targetEntity: Exam::class,
        mappedBy: "course",
    )]
    protected Collection $exams;

    /**
     * Parent course.
     */
    #[ManyToOne(
        targetEntity: Course::class,
        inversedBy: "children",
    )]
    #[JoinColumn(
        name: "parent_code",
        referencedColumnName: "code",
    )]
    protected ?Course $parent = null;

    /**
     * Children of this course.
     */
    #[OneToMany(
        targetEntity: Course::class,
        mappedBy: "parent",
    )]
    protected Collection $children;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->exams = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    /**
     * Get the course code.
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Get the course name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the course URL.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Get the last year the course has been given.
     *
     * @return int
     */
    public function getYear(): int
    {
        return $this->year;
    }

    /**
     * Get the last quartile the course has been given.
     *
     * @return string
     */
    public function getQuartile(): string
    {
        return $this->quartile;
    }

    /**
     * Get all exams belonging to this study.
     *
     * @return Collection
     */
    public function getExams(): Collection
    {
        return $this->exams;
    }

    /**
     * Set the course code.
     *
     * @param string $code
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * Set the course name.
     *
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Set the course URL.
     *
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * Set the parent course.
     */
    public function setParent(Course $parent): void
    {
        $parent->addChild($this);
        $this->parent = $parent;
    }

    /**
     * Add a child.
     */
    public function addChild(Course $child): void
    {
        $this->children[] = $child;
    }

    /**
     * Set the last year the course has been given.
     *
     * @param int $year
     */
    public function setYear(int $year): void
    {
        $this->year = $year;
    }

    /**
     * Set the last quartile the course has been given.
     *
     * @param string $quartile
     */
    public function setQuartile(string $quartile): void
    {
        if (
            !in_array(
                $quartile,
                [
                    self::QUARTILE_Q1,
                    self::QUARTILE_Q2,
                    self::QUARTILE_Q3,
                    self::QUARTILE_Q4,
                    self::QUARTILE_INTERIM,
                ]
            )
        ) {
            throw new InvalidArgumentException('Invalid argument supplied, must be a valid quartile.');
        }
        $this->quartile = $quartile;
    }

    /**
     * Get the parent course.
     *
     * @return Course|null
     */
    public function getParent(): ?Course
    {
        return $this->parent;
    }

    /**
     * Get all children courses.
     *
     * @return Collection
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * Add an exam.
     */
    public function addExam(Exam $exam): void
    {
        $this->exams[] = $exam;
    }

    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId(): string
    {
        return 'course';
    }
}
