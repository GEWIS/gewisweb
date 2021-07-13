<?php

namespace Education\Model;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Course.
 *
 * @ORM\Entity
 */
class Course implements ResourceInterface
{
    const QUARTILE_Q1 = 'q1';
    const QUARTILE_Q2 = 'q2';
    const QUARTILE_Q3 = 'q3';
    const QUARTILE_Q4 = 'q4';
    const QUARTILE_INTERIM = 'interim';

    /**
     * Course code.
     *
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    protected $code;

    /**
     * Course name.
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * Course url.
     *
     * @ORM\Column(type="string")
     */
    protected $url;

    /**
     * Last year the course has been given.
     *
     * @ORM\Column(type="integer")
     */
    protected $year;

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
     *
     * @ORM\Column(type="string")
     */
    protected $quartile;

    /**
     * The studies that apply to the course.
     *
     * @ORM\ManyToMany(targetEntity="Education\Model\Study", inversedBy="courses")
     * @ORM\JoinTable(name="CoursesStudies",
     *      joinColumns={@ORM\JoinColumn(name="course_code", referencedColumnName="code")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="study_id", referencedColumnName="id")}
     * )
     */
    protected $studies;

    /**
     * Exams (and summaries) in this course.
     *
     * @ORM\OneToMany(targetEntity="Education\Model\Exam", mappedBy="course")
     */
    protected $exams;

    /**
     * Parent course.
     *
     * @ORM\ManyToOne(targetEntity="Education\Model\Course", inversedBy="children")
     * @ORM\JoinColumn(name="parent_code", referencedColumnName="code")
     */
    protected $parent;

    /**
     * Children of this course.
     *
     * @ORM\OneToMany(targetEntity="Education\Model\Course", mappedBy="parent")
     */
    protected $children;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->studies = new ArrayCollection();
        $this->exams = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    /**
     * Get the course code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Get the course name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the course URL.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Get the studies for this course.
     *
     * @return ArrayCollection
     */
    public function getStudies()
    {
        return $this->studies;
    }

    /**
     * Get the last year the course has been given.
     *
     * @return int
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * Get the last quartile the course has been given.
     *
     * @return string
     */
    public function getQuartile()
    {
        return $this->quartile;
    }

    /**
     * Get all exams belonging to this study.
     *
     * @return ArrayCollection
     */
    public function getExams()
    {
        return $this->exams;
    }

    /**
     * Set the course code.
     *
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * Set the course name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Set the course URL.
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Add a study.
     *
     * @param Study $study
     */
    public function addStudy(Study $study)
    {
        if (!$this->studies->contains($study)) {
            $study->addCourse($this);
            $this->studies[] = $study;
        }
    }

    /**
     * Add multiple studies.
     *
     * @param array $studies
     */
    public function addStudies($studies)
    {
        foreach ($studies as $study) {
            $this->addStudy($study);
        }
    }

    /**
     * Set the parent course.
     *
     * @param Course $parent
     */
    public function setParent(Course $parent)
    {
        $parent->addChild($this);
        $this->parent = $parent;
    }

    /**
     * Add a child.
     *
     * @param Course $child
     */
    public function addChild(Course $child)
    {
        $this->children[] = $child;
    }

    /**
     * Remove a study.
     *
     * @param Study $study
     */
    public function removeStudy(Study $study)
    {
        $study->removeCourse($this);
        $this->studies->removeElement($study);
    }

    /**
     * Remove multiple studies.
     *
     * @param array $studies
     */
    public function removeStudies($studies)
    {
        foreach ($studies as $study) {
            $this->removeStudy($study);
        }
    }

    /**
     * Set the last year the course has been given.
     *
     * @param int $year
     */
    public function setYear($year)
    {
        $this->year = $year;
    }

    /**
     * Set the last quartile the course has been given.
     *
     * @param string $quartile
     */
    public function setQuartile($quartile)
    {
        if (
            !in_array(
                $quartile,
                [
                self::QUARTILE_Q1,
                self::QUARTILE_Q2,
                self::QUARTILE_Q3,
                self::QUARTILE_Q4,
                self::QUARTILE_INTERIM
                ]
            )
        ) {
            throw new InvalidArgumentException("Invalid argument supplied, must be a valid quartile.");
        }
        $this->quartile = $quartile;
    }

    /**
     * Get the parent course.
     *
     * @return Course
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Get all children courses.
     *
     * @return ArrayCollection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Add an exam.
     *
     * @param Exam $exam
     */
    public function addExam(Exam $exam)
    {
        $this->exams[] = $exam;
    }

    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'course';
    }
}
