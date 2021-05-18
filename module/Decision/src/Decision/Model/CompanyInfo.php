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
class CompanyInfo{
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
    protected $contactName;

    /**
     * @ORM\Column(type="string")
     */
    protected $address;

    /**
     * @ORM\Column(type="string")
     */
    protected $email;

    /**
     * @ORM\Column(type="integer")
     */
    protected $phone;

    /**
     * @ORM\Column(type="integer")
     */
    protected $highlightCredits;

    /**
     * @ORM\Column(type="integer")
     */
    protected $bannerCredits;

    /**
     * Get the company id.
     *
     * @return integer
     */
    function getId(){
        return $this->id;
    }

    /**
     * Get the company name.
     *
     * @return string
     */
    function getName(){
        return $this->name;
    }

    /**
     * Get the company's contact name.
     *
     * @return string
     */
    function getContactName(){
        return $this->contactName;
    }

    /**
     * Get the company's address.
     *
     * @return string
     */
    function getAddress(){
        return $this->address;
    }

    /**
     * Get the company's email.
     *
     * @return string
     */
    function getEmail(){
        return $this->email;
    }

    /**
     * Get the company's phone number.
     *
     * @return integer
     */
    function getPhone(){
        return $this->phone;
    }



    /**
     * Get the companys's number of highlight credits.
     *
     * @return integer
     */
    function getHighlightCredits(){
        return $this->highlightCredits;
    }

    /**
     * Get the companys's number of banner credits.
     *
     * @return integer
     */
    function getBannerCredits(){
        return $this->bannerCredits;
    }
}
