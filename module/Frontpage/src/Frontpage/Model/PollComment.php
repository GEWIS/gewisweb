<?php

namespace Frontpage\Model;

use Doctrine\ORM\Mapping as ORM;
use User\Model\User;
use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * Poll comment
 *
 * @ORM\Entity
 */
class PollComment implements ResourceInterface
{
    /**
     * Poll comment ID.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * Referenced poll.
     *
     * @ORM\ManyToOne(targetEntity="Frontpage\Model\Poll", inversedBy="comments")
     * @ORM\JoinColumn(name="poll_id",referencedColumnName="id")
     */
    protected $poll;

    /**
     * User that posted the comment.
     *
     * @ORM\ManyToOne(targetEntity="User\Model\User")
     * @ORM\JoinColumn(name="user_lidnr",referencedColumnName="lidnr")
     */
    protected $user;

    /**
     * Author of the comment.
     *
     * @ORM\Column(type="string")
     */
    protected $author;

    /**
     * Comment content.
     *
     * @ORM\Column(type="text")
     */
    protected $content;

    /**
     * Comment date.
     *
     * @ORM\Column(type="datetime")
     */
    protected $createdOn;

    /**
     * Get the comment ID.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the poll.
     *
     * @return Poll
     */
    public function getPoll()
    {
        return $this->poll;
    }

    /**
     * Set the poll.
     *
     * @param Poll $poll
     */
    public function setPoll(Poll $poll)
    {
        $this->poll = $poll;
    }

    /**
     * Get the user.
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the user.
     *
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get the author.
     *
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set the author.
     *
     * @param string $author ;
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    }

    /**
     * Get the content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set the content.
     *
     * @param string $content
     */
    public function setContent($content)
    {
        return $this->content = $content;
    }

    /**
     * Get the creation date.
     *
     * @return \DateTime
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }

    /**
     * Set the creation date.
     *
     * @param string $createdOn
     */
    public function setCreatedOn(\DateTime $createdOn)
    {
        $this->createdOn = $createdOn;
    }

    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'poll_comment';
    }
}
