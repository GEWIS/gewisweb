<?php

namespace Company\Model\ApprovalModel;


use Company\Model\JobCategory;
use Company\Model\JobSector;

class ApprovalVacancyTest extends \PHPUnit_Framework_TestCase
{

    public function testApprovalVacancyInitialState()
    {
        $vacancy = new ApprovalVacancy();

        $this->assertNull($vacancy->getId());
        $this->assertNull($vacancy->getName());
        $this->assertNull($vacancy->getSlugName());
        $this->assertNull($vacancy->getCategory());
        $this->assertNull($vacancy->getContactName());
        $this->assertNull($vacancy->getEmail());
        $this->assertNull($vacancy->getPhone());
        $this->assertNull($vacancy->getSectors());
        $this->assertNull($vacancy->getHours());
        $this->assertNull($vacancy->getStartingDate());
        $this->assertNull($vacancy->getWebsite());
        $this->assertNull($vacancy->getLocation());
        $this->assertFalse($vacancy->getRejected());
        $this->assertNull($vacancy->getLanguage());
    }

    public function testId() {
        $vacancy = new ApprovalVacancy();

        $this->assertNull($vacancy->getId());
        $vacancy->setId(1);
        $this->assertEquals(1, $vacancy->getId());
        $vacancy->setId(99999);
        $this->assertEquals(99999, $vacancy->getId());
        $vacancy->setId(null);
        $this->assertNull($vacancy->getId());
    }

    public function testName() {
        $vacancy = new ApprovalVacancy();

        $this->assertNull($vacancy->getName());
        $vacancy->setName("testName");
        $this->assertEquals("testName", $vacancy->getName());
        $vacancy->setName(null);
        $this->assertNull($vacancy->getName());
    }

    public function testSlugName() {
        $vacancy = new ApprovalVacancy();

        $this->assertNull($vacancy->getSlugName());
        $vacancy->setSlugName("testSlug");
        $this->assertEquals("testSlug", $vacancy->getSlugName());
        $vacancy->setSlugName(null);
        $this->assertNull($vacancy->getSlugName());
    }

    public function testCategory() {
        $vacancy = new ApprovalVacancy();
        $category = new JobCategory();
        $category->setId(1);
        $category->setName("testCategory");
        $category->setSlug("testCategorySlug");

        $this->assertNull($vacancy->getCategory());
        $vacancy->setCategory($category);
        $this->assertEquals($category, $vacancy->getCategory());
        $this->assertEquals(1, $category->getId());
        $this->assertEquals("testCategory", $category->getName());
        $this->assertEquals("testCategorySlug", $category->getSlug());
        $vacancy->setCategory(null);
        $this->assertNull($vacancy->getCategory());
    }

    public function testSector() {
        $vacancy = new ApprovalVacancy();
        $sector = new JobSector();
        $sector->setId(1);
        $sector->setName("testSector");
        $sector->setSlug("testSectorSlug");

        $this->assertNull($vacancy->getSectors());
        $vacancy->setSectors($sector);
        $this->assertEquals($sector, $vacancy->getSectors());
        $this->assertEquals(1, $sector->getId());
        $this->assertEquals("testSector", $sector->getName());
        $this->assertEquals("testSectorSlug", $sector->getSlug());
        $vacancy->setSectors(null);
        $this->assertNull($vacancy->getSectors());
    }

    public function testContactName() {
        $vacancy = new ApprovalVacancy();

        $this->assertNull($vacancy->getContactName());
        $vacancy->setContactName("testName");
        $this->assertEquals("testName", $vacancy->getContactName());
        $vacancy->setContactName(null);
        $this->assertNull($vacancy->getContactName());
    }

    public function testEmail() {
        $vacancy = new ApprovalVacancy();

        $this->assertNull($vacancy->getEmail());
        $vacancy->setEmail("test@email.com");
        $this->assertEquals("test@email.com", $vacancy->getEmail());
        $vacancy->setEmail(null);
        $this->assertNull($vacancy->getEmail());
    }

    public function testPhone() {
        $vacancy = new ApprovalVacancy();

        $this->assertNull($vacancy->getPhone());
        $vacancy->setPhone("0612345678");
        $this->assertEquals("0612345678", $vacancy->getPhone());
        $vacancy->setPhone("31612345678");
        $this->assertEquals("31612345678", $vacancy->getPhone());
        $vacancy->setPhone(null);
        $this->assertNull($vacancy->getPhone());
    }

    public function testHours() {
        $vacancy = new ApprovalVacancy();

        $this->assertNull($vacancy->getHours());
        $vacancy->setHours("Part Time");
        $this->assertEquals("Part Time", $vacancy->getHours());
        $vacancy->setHours("Full Time");
        $this->assertEquals("Full Time", $vacancy->getHours());
        $vacancy->setHours(null);
        $this->assertNull($vacancy->getHours());
    }

    public function testStartingDate() {
        $vacancy = new ApprovalVacancy();
        $testDate = new \DateTime('NOW');

        $this->assertNull($vacancy->getStartingDate());
        $vacancy->setStartingDate($testDate);
        $this->assertEquals($testDate, $vacancy->getStartingDate());
        $vacancy->setStartingDate(null);
        $this->assertNull($vacancy->getStartingDate());
    }

    public function testWebsite() {
        $vacancy = new ApprovalVacancy();

        $this->assertNull($vacancy->getWebsite());
        $vacancy->setWebsite("https://test.com");
        $this->assertEquals("https://test.com", $vacancy->getWebsite());
        $vacancy->setWebsite(null);
        $this->assertNull($vacancy->getWebsite());
    }

    public function testLocation() {
        $vacancy = new ApprovalVacancy();

        $this->assertNull($vacancy->getLocation());
        $vacancy->setLocation("Somewhere Avenue 26");
        $this->assertEquals("Somewhere Avenue 26", $vacancy->getLocation());
        $vacancy->setLocation(null);
        $this->assertNull($vacancy->getLocation());
    }

    public function testLanguage() {
        $vacancy = new ApprovalVacancy();

        $this->assertNull($vacancy->getLanguage());
        $vacancy->setLanguage("nl");
        $this->assertEquals("nl", $vacancy->getLanguage());
        $vacancy->setLanguage("en");
        $this->assertEquals("en", $vacancy->getLanguage());
        $vacancy->setLanguage(null);
        $this->assertNull($vacancy->getLanguage());
    }

    public function testRejected() {
        $vacancy = new ApprovalVacancy();

        $this->assertFalse($vacancy->getRejected());
        $vacancy->setRejected(true);
        $this->assertTrue($vacancy->getRejected());
        $vacancy->setRejected(false);
        $this->assertFalse($vacancy->getRejected());
    }
}
