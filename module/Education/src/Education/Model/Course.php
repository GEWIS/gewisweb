<?php

namespace Education\Model;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Zend\Permissions\Acl\Resource\ResourceInterface;

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
     * Id column.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * Course code.
     *
     * @todo at unique constraint
     *
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

    // TODO: create study entity and add relation to that
    // so we can have multiple studies referred from a course

    /**
     * Last year the course has been given.
     *
     * @ORM\Column(type="int")
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
     * Get the ID.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
        if (!in_array($quartile, array(
                self::QUARTILE_Q1,
                self::QUARTILE_Q2,
                self::QUARTILE_Q3,
                self::QUARTILE_Q4,
                self::QUARTILE_INTERIM
            ))) {
            throw new \InvalidArgumentException("Invalid argument supplied, must be a valid quartile.");
        }
        $this->quartile = $quartile;
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
