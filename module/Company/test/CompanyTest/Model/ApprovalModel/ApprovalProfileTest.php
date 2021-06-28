<?php

namespace Company\Model\ApprovalModel;


class ApprovalProfileTest extends \PHPUnit_Framework_TestCase
{

    public function testCompanyInitialState()
    {
        $profile = new ApprovalProfile();

        $this->assertNull($profile->getId());
        $this->assertNull($profile->getName());
        $this->assertNull($profile->getSlugName());
        $this->assertNull($profile->getContactName());
        $this->assertNull($profile->getAddress());
        $this->assertNull($profile->getContactEmail());
        $this->assertNull($profile->getEmail());
        $this->assertNull($profile->getPhone());
        $this->assertNull($profile->getBannerCredits());
        $this->assertNull($profile->getHighlightCredits());
        $this->assertFalse($profile->getRejected());


        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $profile->getPackages());
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $profile->getTranslations());
    }

    public function testId() {
        $profile = new ApprovalProfile();

        $this->assertNull($profile->getId());
        $profile->setId(1);
        $this->assertEquals(1, $profile->getId());
        $profile->setId(99999);
        $this->assertEquals(99999, $profile->getId());
        $profile->setId(null);
        $this->assertNull($profile->getId());
    }

    public function testName() {
        $profile = new ApprovalProfile();

        $this->assertNull($profile->getName());
        $profile->setName("testName");
        $this->assertEquals("testName", $profile->getName());
        $profile->setName(null);
        $this->assertNull($profile->getName());
    }

    public function testSlugName() {
        $profile = new ApprovalProfile();

        $this->assertNull($profile->getSlugName());
        $profile->setSlugName("testSlug");
        $this->assertEquals("testSlug", $profile->getSlugName());
        $profile->setSlugName(null);
        $this->assertNull($profile->getSlugName());
    }

    public function testContactName() {
        $profile = new ApprovalProfile();

        $this->assertNull($profile->getContactName());
        $profile->setContactName("testContactName");
        $this->assertEquals("testContactName", $profile->getContactName());
        $profile->setContactName(null);
        $this->assertNull($profile->getContactName());
    }

    public function testAddress() {
        $profile = new ApprovalProfile();

        $this->assertNull($profile->getAddress());
        $profile->setAddress("testAddress");
        $this->assertEquals("testAddress", $profile->getAddress());
        $profile->setAddress(null);
        $this->assertNull($profile->getAddress());
    }

    public function testBannerCredits() {
        $profile = new ApprovalProfile();

        $this->assertNull($profile->getBannerCredits());
        $profile->setBannerCredits(0);
        $this->assertEquals(0, $profile->getBannerCredits());
        $profile->setBannerCredits(10);
        $this->assertEquals(10, $profile->getBannerCredits());
        $profile->setBannerCredits(99999);
        $this->assertEquals(99999, $profile->getBannerCredits());
        $profile->setBannerCredits(null);
        $this->assertNull($profile->getBannerCredits());
    }

    public function testHighlightCredits() {
        $profile = new ApprovalProfile();

        $this->assertNull($profile->getHighlightCredits());
        $profile->setHighlightCredits(0);
        $this->assertEquals(0, $profile->getHighlightCredits());
        $profile->setHighlightCredits(10);
        $this->assertEquals(10, $profile->getHighlightCredits());
        $profile->setHighlightCredits(99999);
        $this->assertEquals(99999, $profile->getHighlightCredits());
        $profile->setHighlightCredits(null);
        $this->assertNull($profile->getHighlightCredits());
    }

    public function testEmail() {
        $profile = new ApprovalProfile();

        $this->assertNull($profile->getEmail());
        $profile->setEmail("test@email.com");
        $this->assertEquals("test@email.com", $profile->getEmail());
        $profile->setEmail(null);
        $this->assertNull($profile->getEmail());
    }

    public function testContactEmail() {
        $profile = new ApprovalProfile();

        $this->assertNull($profile->getContactEmail());
        $profile->setContactEmail("test@email.com");
        $this->assertEquals("test@email.com", $profile->getContactEmail());
        $profile->setContactEmail(null);
        $this->assertNull($profile->getContactEmail());
    }

    public function testPhone() {
        $profile = new ApprovalProfile();

        $this->assertNull($profile->getPhone());
        $profile->setPhone("0612345678");
        $this->assertEquals("0612345678", $profile->getPhone());
        $profile->setPhone("31612345678");
        $this->assertEquals("31612345678", $profile->getPhone());
        $profile->setPhone(null);
        $this->assertNull($profile->getPhone());
    }

    public function testRejected() {
        $profile = new ApprovalProfile();

        $this->assertFalse($profile->getRejected());
        $profile->setRejected(true);
        $this->assertTrue($profile->getRejected());
        $profile->setRejected(false);
        $this->assertFalse($profile->getRejected());
    }

}
