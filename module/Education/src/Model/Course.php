<?php

declare(strict_types=1);

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
     * Exams (and summaries) in this course.
     */
    #[OneToMany(
        targetEntity: CourseDocument::class,
        mappedBy: "course",
    )]
    protected Collection $documents;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
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
     * Get all exams belonging to this study.
     *
     * @psalm-return Collection<int, Exam|Summary>
     * @return Collection
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
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

    public function toArray(): array
    {
        return [
            'code' => $this->getCode(),
            'name' => $this->getName(),
        ];
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
