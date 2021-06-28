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


    public function testBannerCredits() {
        $company = new Company();

        $this->assertNull($company->getBannerCredits());
        $company->setBannerCredits(0);
        $this->assertEquals(0, $company->getBannerCredits());
        $company->setBannerCredits(10);
        $this->assertEquals(10, $company->getBannerCredits());
        $company->setBannerCredits(99999);
        $this->assertEquals(99999, $company->getBannerCredits());
        $company->setBannerCredits(null);
        $this->assertNull($company->getBannerCredits());
    }

    public function testHighlightCredits() {
        $company = new Company();

        $this->assertNull($company->getHighlightCredits());
        $company->setHighlightCredits(0);
        $this->assertEquals(0, $company->getHighlightCredits());
        $company->setHighlightCredits(10);
        $this->assertEquals(10, $company->getHighlightCredits());
        $company->setHighlightCredits(99999);
        $this->assertEquals(99999, $company->getHighlightCredits());
        $company->setHighlightCredits(null);
        $this->assertNull($company->getHighlightCredits());
    }

    public function testContactEmail() {
        $company = new Company();

        $this->assertNull($company->getContactEmail());
        $company->setContactEmail("test@email.com");
        $this->assertEquals("test@email.com", $company->getContactEmail());
        $company->setContactEmail(null);
        $this->assertNull($company->getContactEmail());
    }

}
