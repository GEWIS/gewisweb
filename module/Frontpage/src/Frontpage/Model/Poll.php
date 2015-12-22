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
     * Poll options.
     *
     * @ORM\OneToMany(targetEntity="PollOption", mappedBy="poll", cascade={"persist", "remove"})
     */
    protected $options;

    /**
     * Poll comments.
     *
     * @ORM\OneToMany(targetEntity="PollComment", mappedBy="poll", cascade={"persist", "remove"})
     */
    protected $comments;

    /**
     * Who approved this poll. If null then nobody approved it.
     *
     * @ORM\ManyToOne(targetEntity="User\Model\User")
     * @ORM\JoinColumn(referencedColumnName="lidnr")
     */
    protected $creator;

    /**
     * Who approved this poll. If null then nobody approved it.
     *
     * @ORM\ManyToOne(targetEntity="User\Model\User")
     * @ORM\JoinColumn(referencedColumnName="lidnr", nullable=true)
     */
    protected $approver;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return date
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
     * @return \User\Model\User
     */
    public function getApprover()
    {
        return $this->approver;
    }

    /**
     * @return \User\Model\User
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @param date $expiryDate
     */
    public function setExpiryDate($expiryDate)
    {
        $this->expiryDate = $expiryDate;
    }

    /**
     * @param string $englishQuestion
     */
    public function setEnglishQuestion($englishQuestion)
    {
        $this->englishQuestion = $englishQuestion;
    }

    /**
     * @param string $dutchQuestion
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
     * @param \User\Model\User $approver
     */
    public function setApprover($approver)
    {
        $this->approver = $approver;
    }

    /**
     * @param \User\Model\User $creator
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;
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
