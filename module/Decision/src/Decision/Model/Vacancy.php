<?php

namespace Decision\Model;

use Decision\Model\SubDecision\Installation;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;


/**
 * vacancy model.
 *
 * @ORM\Entity
 */
class vacancy{
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

    /**
     * @ORM\Column(type="string")
     */
    protected $companyName;

    /**
     * Get the company name.
     *
     * @return string
     */
    function getName(){
        return $this->name;
    }

    /**
     * Get the vacancy's description.
     *
     * @return string
     */
    function getDescription(){
        return $this->description;
    }
}
