<?php
namespace Activity\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Activity model
 *
 * @ORM\Entity
 */
class ActivitySignup
{
    /**
     * ID for the activity
     *
     * @ORM\Id
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * Who is subscribed
     *
     * @ORM\Column(nullable=false)
     * @ORM\ManyToOne(targetEntity="Activity\Model\Activity", inversedBy="roles")
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    protected $activity_id;

    /**
     * Who is subscribed
     *
     * @ORM\Column(nullable=false)
     * @ORM\ManyToOne(targetEntity="User\Model\User", inversedBy="roles")
     * @ORM\JoinColumn(referencedColumnName="lidnr")
     */
    protected $user_id;

    public function setAcitivityId($activityId)
    {
        $this->activity_id = $activityId;
    }

    public function setUserId($userId)
    {
        $this->user_id = $userId;
    }
}