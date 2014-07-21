<?php
namespace Activity\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Activity model
 *
 * @ORM\Entity
 */
class Activity
{
    /**
     * ID for the activity
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
	protected $id;

    /**
     * The date and time the activity starts
     *
     * @ORM\Column(type="datetime")
     */
	protected $beginTime;

    /**
     * The date and time the activity ends
     *
     * @ORM\Column(type="datetime")
     */
	protected $endTime;


    /**
     * The location the activity is held at
     *
     * @ORM\Column(type="string")
     */
    protected $location;


    /**
     * How much does it cost. 0 = free!
     *
     * @ORM\Column(type="integer")
     */
    protected $costs;

    /**
     * Are people able to sign up for this activity?
     *
     * @ORM\Column(type="boolean")
     */
    protected $canSignUp;

    /**
     * Are people outside of GEWIS allowed to sign up
     * N.b. if $canSignUp is false, this column does not matter
     *
     * @ORM\Column(type="boolean")
     */
    protected $onlyGEWIS;

    // TODO -> FK's
    protected $approver;
    protected $creator;
    protected $organ;


}