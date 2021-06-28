<?php

namespace Company\Model\ApprovalModel;


use Company\Model\Company;

class ApprovalCompanyI18nTest extends \PHPUnit_Framework_TestCase
{
    public function testApprovalCompanyI18nInitialState()
    {
        $profile = new ApprovalProfile();
        $locale = 'nl';
        $info = new ApprovalCompanyI18n($locale, $profile);

        $this->assertNull($info->getId());
        $this->assertNotNull($info->getCompany());
        $this->assertNull($info->getSlogan());
        $this->assertNull($info->getLogo());
        $this->assertEmpty($info->getDescription());
        $this->assertEmpty($info->getWebsite());
        $this->assertNotNull($info->getLanguage());
        $this->assertFalse($info->getRejected());
    }

    public function testId() {
        $profile = new ApprovalProfile();
        $locale = 'nl';
        $info = new ApprovalCompanyI18n($locale, $profile);

        $this->assertNull($info->getId());
        $info->setId(1);
        $this->assertEquals(1, $info->getId());
        $info->setId(99999);
        $this->assertEquals(99999, $info->getId());
        $info->setId(null);
        $this->assertNull($info->getId());
    }

    public function testCompany() {
        $profile = new ApprovalProfile();
        $locale = 'nl';
        $info = new ApprovalCompanyI18n($locale, $profile);

        $newProfile = new ApprovalProfile();

        $this->assertEquals($profile, $info->getCompany());
        $info->setCompany($newProfile);
        $this->assertEquals($newProfile, $info->getCompany());
    }

    public function testSlogan() {
        $profile = new ApprovalProfile();
        $locale = 'nl';
        $info = new ApprovalCompanyI18n($locale, $profile);


        $this->assertNull($info->getSlogan());
        $info->setSlogan("testSlogan");
        $this->assertEquals("testSlogan", $info->getSlogan());
        $info->setSlogan(null);
        $this->assertNull($info->getSlogan());
    }

    public function testLogo() {
        $profile = new ApprovalProfile();
        $locale = 'nl';
        $info = new ApprovalCompanyI18n($locale, $profile);


        $this->assertNull($info->getLogo());
        $info->setLogo("path/to/logo");
        $this->assertEquals("path/to/logo", $info->getLogo());
        $info->setLogo(null);
        $this->assertNull($info->getLogo());
    }

    public function testDescription() {
        $profile = new ApprovalProfile();
        $locale = 'nl';
        $info = new ApprovalCompanyI18n($locale, $profile);


        $this->assertEmpty($info->getDescription());
        $info->setDescription("test description");
        $this->assertEquals("test description", $info->getDescription());
        $info->setDescription(null);
        $this->assertNull($info->getDescription());
    }

    public function testWebsite() {
        $profile = new ApprovalProfile();
        $locale = 'nl';
        $info = new ApprovalCompanyI18n($locale, $profile);


        $this->assertEmpty($info->getWebsite());
        $info->setWebsite("https://test.com");
        $this->assertEquals("https://test.com", $info->getWebsite());
        $info->setWebsite(null);
        $this->assertNull($info->getWebsite());
    }

    public function testLanguage() {
        $profile = new ApprovalProfile();
        $locale = 'nl';
        $info = new ApprovalCompanyI18n($locale, $profile);

        $this->assertEquals("nl", $info->getLanguage());
        $info->setLanguage("en");
        $this->assertEquals("en", $info->getLanguage());
        $info->setLanguage(null);
        $this->assertNull($info->getLanguage());
    }

    public function testRejected() {
        $profile = new ApprovalProfile();
        $locale = 'nl';
        $info = new ApprovalCompanyI18n($locale, $profile);

        $this->assertFalse($info->getRejected());
        $info->setRejected(true);
        $this->assertTrue($info->getRejected());
        $info->setRejected(false);
        $this->assertFalse($info->getRejected());
    }

}
