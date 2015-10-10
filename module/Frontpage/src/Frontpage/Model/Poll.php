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
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'poll';
    }
}
