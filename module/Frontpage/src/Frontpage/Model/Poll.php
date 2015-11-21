<?php

namespace Frontpage\Model;

use Doctrine\ORM\Mapping as ORM;
use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * Poll
 *
 * @ORM\Entity
 * @ORM\Table(name="Poll")
 */
class Poll implements ResourceInterface
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->options = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Poll ID.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * The date the poll expires.
     *
     * @ORM\Column(type="date")
     */
    protected $expiryDate;

    /**
     * The dutch question for the poll.
     *
     * @ORM\Column(type="string")
     */
    protected $dutchQuestion;

    /**
     * The english translation of the question if available.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $englishQuestion;

    /**
     * @ORM\OneToMany(targetEntity="PollOption", mappedBy="poll", cascade={"persist", "remove"})
     */
    protected $options;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getExpiryDate()
    {
        return $this->expiryDate;
    }

    /**
     * @return string
     */
    public function getDutchQuestion()
    {
        return $this->dutchQuestion;
    }

    /**
     * @return string
     */
    public function getEnglishQuestion()
    {
        return $this->englishQuestion;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param mixed $expiryDate
     */
    public function setExpiryDate($expiryDate)
    {
        $this->expiryDate = $expiryDate;
    }

    /**
     * @param mixed $englishQuestion
     */
    public function setEnglishQuestion($englishQuestion)
    {
        $this->englishQuestion = $englishQuestion;
    }

    /**
     * @param mixed $dutchQuestion
     */
    public function setDutchQuestion($dutchQuestion)
    {
        $this->dutchQuestion = $dutchQuestion;
    }

    /**
     * Adds options to the poll
     *
     * @param ArrayCollection $options
     */
    public function addOptions($options)
    {
        foreach ($options as $option) {
            $option->setPoll($this);
            $this->options->add($option);
        }
    }

    /**
     * Removes options from the poll
     *
     * @param ArrayCollection $options
     */
    public function removeOptions($options)
    {
        foreach ($options as $option) {
            $option->setPoll(null);
            $this->options->removeElement($option);
        }
    }


    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'poll';
    }
}
