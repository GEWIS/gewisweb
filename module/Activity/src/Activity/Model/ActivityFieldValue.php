<?php

namespace Activity\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Activity model.
 *
 * @ORM\Entity
 */
class ActivityFieldValue
{
    /**
     * ID for the field value.
     *
     * @ORM\Id
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * Field which the value belongs to.
     * 
     * @ORM\ManyToOne(targetEntity="ActivityField")
     * @ORM\JoinColumn(name="field_id",referencedColumnName="id")
     */
    protected $field;

    /**
     * Signup which the value belongs to.
     *
     * @ORM\ManyToOne(targetEntity="ActivitySignup")
     * @ORM\JoinColumn(name="signup_id", referencedColumnName="id")
     */
    protected $signup;

    /**
     * The value of the assoctiated field.
     * 
     * @ORM\Column(type="string")
     */
    protected $value;
}
