<?php

namespace CompanyTest\Model;

use Company\Model\Company;
use Doctrine\Common\Collections\ArrayCollection as ArrayCollection;

class CompanyTest extends \PHPUnit_Framework_TestCase
{
    protected $data;


    public function testCompanyInitialState()
    {
        $company = new Company();

        $this->assertNull($company->getId());
        $this->assertNull($company->getContactEmail());
        $this->assertNull($company->getName());
        $this->assertNull($company->getSlugName());
        $this->assertNull($company->getContactName());
        $this->assertNull($company->getAddress());
        $this->assertNull($company->getContactEmail());
        $this->assertNull($company->getEmail());
        $this->assertNull($company->getPhone());
        $this->assertNull($company->getBannerCredits());
        $this->assertNull($company->getHighlightCredits());


        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $company->getPackages());
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $company->getTranslations());
    }


    public function testExchangeArrayDefaultCredits() {
        $company = new Company();

        $data["name"] = "Test Company";
        $data["id"] = 1;
        $data["languages"]["en"] = [];
        $data["languages"]["nl"] = [];


        $company->ExchangeArray($data);

    }

}
