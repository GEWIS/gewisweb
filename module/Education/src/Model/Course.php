<?php

declare(strict_types=1);

namespace Education\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
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
     *
     * @var Collection<array-key, CourseDocument>
     */
    #[OneToMany(
        targetEntity: CourseDocument::class,
        mappedBy: 'course',
    )]
    protected Collection $documents;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
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
