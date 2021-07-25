<?php

namespace Education\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    Id,
    ManyToMany,
};
use InvalidArgumentException;

/**
 * Study.
 */
#[Entity]
class Study
{
    public const PHASE_BACHELOR = 'bachelor';
    public const PHASE_MASTER = 'master';

    /**
     * Study ID.
     *
     * This is given by the OASE API.
     */
    #[Id]
    #[Column(type: "integer")]
    protected int $id;

    /**
     * Study name.
     */
    #[Column(type: "string")]
    protected string $name;

    /**
     * Phase of the study.
     */
    #[Column(type: "string")]
    protected string $phase;

    /**
     * Group ID from OASE.
     */
    #[Column(type: "integer")]
    protected int $groupId;

    /**
     * Courses belonging to this study.
     */
    #[ManyToMany(
        targetEntity: "Education\Model\Course",
        mappedBy: "studies",
    )]
    protected ArrayCollection $courses;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->courses = new ArrayCollection();
    }

    /**
     * Get the ID.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the study name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the phase.
     *
     * @return string
     */
    public function getPhase(): string
    {
        return $this->phase;
    }

    /**
     * Get the group id.
     *
     * @return int
     */
    public function getGroupId(): int
    {
        return $this->groupId;
    }

    /**
     * Get the courses in this study.
     *
     * @return ArrayCollection
     */
    public function getCourses(): ArrayCollection
    {
        return $this->courses;
    }

    /**
     * Set the ID.
     *
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Set the study name.
     *
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Set the phase.
     *
     * @param string $phase
     *
     * @throws InvalidArgumentException
     */
    public function setPhase(string $phase): void
    {
        if (
            !in_array(
                $phase,
                [
                self::PHASE_BACHELOR,
                self::PHASE_MASTER,
                ]
            )
        ) {
            throw new InvalidArgumentException('Invalid phase given.');
        }
        $this->phase = $phase;
    }

    /**
     * Set the group.
     *
     * @param int $group
     */
    public function setGroupId(int $group): void
    {
        $this->groupId = $group;
    }

    /**
     * Add a course.
     *
     * @param Course $course
     */
    public function addCourse(Course $course): void
    {
        $this->courses[] = $course;
    }

    /**
     * Remove a course.
     *
     * @param Course $course
     */
    public function removeCourse(Course $course): void
    {
        $this->courses->removeElement($course);
    }
}
