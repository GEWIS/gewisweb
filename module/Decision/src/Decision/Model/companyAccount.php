<?php

namespace Decision\Model;

use Decision\Model\SubDecision\Installation;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;


/**
 * Member model.
 *
 * @ORM\Entity
 */
class companyAccount{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * @ORM\Column(type="string")
     */
    protected $description;

    /**
     * @ORM\Column(type="integer")
     */
    protected $active;

    function getName(){
        return $this->name;
    }

    function getDescription(){
        return $this->description;
    }
}
