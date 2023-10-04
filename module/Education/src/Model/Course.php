<?php

declare(strict_types=1);

namespace Education\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OrderBy;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Course.
 */
#[Entity]
class Course implements ResourceInterface
{
    /**
     * Course code.
     */
    #[Id]
    #[Column(type: 'string')]
    protected string $code;

    /**
     * Course name.
     */
    #[Column(type: 'string')]
    protected string $name;

    /**
     * Exams (and summaries) in this course.
     * Ordered by date, from old to recent since documents are not necessarily uploaded in chronological order
     * @var Collection<array-key, CourseDocument>
     */
    #[OneToMany(
        targetEntity: CourseDocument::class,
        mappedBy: 'course',
    )]
    #[OrderBy(value: ['date' => 'ASC'])]
    protected Collection $documents;

    /**
     * Courses that say they are similar to this course
     *
     * @var Collection<array-key, Course>
     */
    #[ManyToMany(
        targetEntity: self::class,
        mappedBy: 'similarCoursesTo',
    )]
    protected Collection $similarCoursesFrom;

    /**
     * Courses similar to this course
     * 
     * @var Collection<array-key, Course>
     */
    #[JoinTable(name: 'SimilarCourse')]
    #[JoinColumn(
        name: 'course_code', 
        referencedColumnName: 'code',
    )]
    #[InverseJoinColumn(
        name: 'similar_course_code', 
        referencedColumnName: 'code',
    )]
    #[ManyToMany(
        targetEntity: self::class, 
        inversedBy: 'similarCoursesFrom',
    )]
    private Collection $similarCoursesTo;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
        $this->similarCoursesFrom = new ArrayCollection();
        $this->similarCoursesTo = new ArrayCollection();
    }

    /**
     * Get the course code.
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Get the course name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get all exams belonging to this study.
     *
     * @return Collection<array-key, CourseDocument>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    /**
     * Set the course code.
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * Set the course name.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return array{
     *     code: string,
     *     name: string,
     * }
     */
    public function toArray(): array
    {
        return [
            'code' => $this->getCode(),
            'name' => $this->getName(),
        ];
    }

    /**
     * Get the resource ID.
     */
    public function getResourceId(): string
    {
        return 'course';
    }
}
