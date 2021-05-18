<?php

namespace Decision\Model;

use Decision\Model\SubDecision\Installation;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;


/**
 * companyinfo model.
 *
 * @ORM\Entity
 */
class CompanyPackageInfo{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $company_id;

    /**
     * @ORM\Column(type="date")
     */
    protected $starts;

    /**
     * @ORM\Column(type="date")
     */
    protected $expires;

    /**
     * @ORM\Column(type="string")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $name;


    /**
     * Get the company id.
     *
     * @return integer
     */
    function getId(){
        return $this->company_id;
    }

    /**
     * Get company package starting date.
     *
     * @return date
     */
    function getStart(){
        return $this->starts;
    }

    /**
     * Get company package ending date.
     *
     * @return date
     */
    function getExpires(){
        return $this->expires;
    }


}
