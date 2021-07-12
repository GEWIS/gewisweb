<?php

namespace Frontpage\Model;

use Doctrine\ORM\Mapping as ORM;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Poll Option
 *
 * @ORM\Entity
 * @ORM\Table(name="PollOption")
 */
class PollOption implements ResourceInterface
{
    /**
     * Poll Option ID.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Frontpage\Model\Poll", inversedBy="options")
     * @ORM\JoinColumn(name="poll_id",referencedColumnName="id")
     */
    protected $poll;

    /**
     * The dutch text for this option.
     *
     * @ORM\Column(type="string")
     */
    protected $dutchText;

    /**
     * The english translation of the option if available.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $englishText;

    /**
     * @ORM\OneToMany(targetEntity="PollVote", mappedBy="pollOption", cascade={"persist", "remove"}, fetch="EXTRA_LAZY")
     */
    protected $votes;

    /**
     * Number of votes not bound to a specific user.
     *
     * @ORM\Column(type="integer")
     */
    protected $anonymousVotes = 0;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Poll
     */
    public function getPoll()
    {
        return $this->poll;
    }

    /**
     * @return string
     */
    public function getDutchText()
    {
        return $this->dutchText;
    }

    /**
     * @return string
     */
    public function getEnglishText()
    {
        return $this->englishText;
    }

    /**
     * Adds a new vote for this poll option
     *
     * @param PollVote $pollVote
     */
    public function addVote($pollVote)
    {
        $pollVote->setPollOption($this);
        $this->votes[] = $pollVote;
    }

    /**
     * @param mixed $poll
     */
    public function setPoll($poll)
    {
        $this->poll = $poll;
    }

    /**
     * @param mixed $dutchText
     */
    public function setDutchText($dutchText)
    {
        $this->dutchText = $dutchText;
    }

    /**
     * @param mixed $englishText
     */
    public function setEnglishText($englishText)
    {
        $this->englishText = $englishText;
    }

    /**
     * @param integer $votes
     */
    public function setAnonymousVotes($votes)
    {
        $this->anonymousVotes = $votes;
    }

    /**
     * Get the number of votes for this poll option.
     *
     * @return integer
     */
    public function getVotesCount()
    {
        return $this->anonymousVotes + (is_null($this->votes) ? 0 : $this->votes->count());
    }

    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'poll_option';
    }
}
