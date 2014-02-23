<?php

namespace Education\Model;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Study.
 *
 * @ORM\Entity
 */
class Study
{

    const PHASE_BACHELOR = 'bachelor';
    const PHASE_MASTER = 'master';

    /**
     * Study ID.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * Study name.
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * Phase of the study.
     *
     * @ORM\Column(type="string")
     */
    protected $phase;

    /**
     * Courses belonging to this study.
     *
     * @ORM\ManyToMany(targetEntity="Education\Model\Course", mappedBy="studies")
     */
    protected $courses;


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
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the study name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the phase.
     *
     * @return string
     */
    public function getPhase()
    {
        return $this->phase;
    }

    /**
     * Get the courses in this study.
     *
     * @return array
     */
    public function getCourses()
    {
        return $this->courses;
    }

    /**
     * Set the study name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Set the phase.
     *
     * @param string $phase
     */
    public function setPhase($phase)
    {
        if (!in_array($phase, array(
            self::PHASE_BACHELOR,
            self::PHASE_MASTER
        ) {
            throw new \InvalidArgumentException("Invalid phase given.");
        }
        $this->phase = $phase;
    }

    /**
     * Add a course.
     *
     * @param Course $course
     */
    public function addCourse(Course $course)
    {
        $this->courses[] = $course;
    }
}
