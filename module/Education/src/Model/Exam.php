<?php

namespace Education\Model;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Exam.
 *
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"exam"="Education\Model\Exam"
 *                      , "summary"="Education\Model\Summary"})
 */
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
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * Date of the exam.
     *
     * @ORM\Column(type="date")
     */
    protected $date;

    /**
     * Filename of the exam.
     *
     * @ORM\Column(type="string")
     */
    protected $filename;

    /**
     * Type of exam. One of {exam, intermediate, answers, summary}.
     *
     * @ORM\Column(type="string")
     */
    protected $examType;

    /**
     * The language of the exam.
     *
     * @ORM\Column(type="string")
     */
    protected $language;

    /**
     * Course belonging to this exam.
     *
     * @ORM\ManyToOne(targetEntity="Education\Model\Course", inversedBy="exams")
     * @ORM\JoinColumn(name="course_code",referencedColumnName="code")
     */
    protected $course;

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
     * Get the date.
     *
     * @return DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Get the filename.
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Get the type.
     *
     * @return string
     */
    public function getExamType()
    {
        return $this->examType;
    }

    /**
     * Get the course.
     *
     * @return Course
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * Get the language.
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set the date.
     */
    public function setDate(DateTime $date)
    {
        $this->date = $date;
    }

    /**
     * Set the type.
     *
     * @param string $examType
     */
    public function setExamType($examType)
    {
        $this->examType = $examType;
    }

    /**
     * Set the filename.
     *
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    /**
     * Set the language.
     *
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * Set the course.
     *
     * @param Course $course
     */
    public function setCourse($course)
    {
        $this->course = $course;
    }

    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'exam';
    }
}
