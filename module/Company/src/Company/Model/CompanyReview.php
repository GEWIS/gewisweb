<?php

namespace Company\Model;

use Doctrine\ORM\Mapping as ORM;
//use Doctrine\Common\Collections\ArrayCollection;
//use Zend\Permissions\Acl\Role\RoleInterface;
//use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * CompanyReview model.
 *
 * @ORM\Entity
 */
class CompanyReview //implements RoleInterface, ResourceInterface
{

    /**
     * The review's id.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * The review's title.
     *
     * @ORM\Column(type="string")
     */
    protected $title;

    /**
     * The reviewer.
     *
     * @ORM\OneToOne(targetEntity="Decision\Model\Member")
     * @ORM\JoinColumn(name="lidnr", referencedColumnName="lidnr")
     */
    protected $reviewer;

    /**
     * The review's content.
     *
     * @ORM\Column(type="text")
     */
    protected $content;

    /**
     * The review's date of publication.
     *
     * @ORM\Column(type="date")
     */
    protected $date;

    /**
     * The review's approval status.
     *
     * @ORM\Column(type="boolean")
     */
    protected $approved;

    /**
     * The approver of the review.
     *
     * @ORM\OneToOne(targetEntity="Decision\Model\Member")
     * @ORM\JoinColumn(name="lidnr", referencedColumnName="lidnr")
     */
    protected $approver;


    /**
     * Constructor
     */
    public function __construct()
    {
        // todo
    }

    /**
     * Get the review's id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the review's title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the review's title.
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get the reviewer.
     *
     * @return Member
     */
    public function getReviewer()
    {
        return $this->reviewer;
    }

    /**
     * Set the reviewer.
     *
     * @param Member $reviewer
     */
    public function setReviewer($reviewer)
    {
        $this->reviewer = $reviewer;
    }

    /**
     * Get the review's content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set the review's content.
     *
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Get the review's date of publication.
     *
     * @return date
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set the review's date of publication.
     *
     * @param date $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * Get the review's approval status.
     *
     * @return boolean
     */
    public function getApproved()
    {
        return $this->approved;
    }

    /**
     * Set the review's approval status.
     *
     * @param boolean $approved
     */
    public function setApproved($approved)
    {
        $this->approved = $approved;
    }

    /**
     * Get the approver of the review.
     *
     * @return Member
     */
    public function getApprover()
    {
        return $this->approver;
    }

    /**
     * Set the approver of the review.
     *
     * @param Member $approver
     */
    public function setApprover($approver)
    {
        $this->approver = $approver;
    }


}
