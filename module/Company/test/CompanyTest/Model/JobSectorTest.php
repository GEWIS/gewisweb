<?php

namespace CompanyTest\Model;


use Company\Model\JobSector;

class JobSectorTest extends \PHPUnit_Framework_TestCase
{

    public function testJobSectorInitialState()
    {
        $jobSector = new JobSector();

        $this->assertNull($jobSector->getId());
        $this->assertNull($jobSector->getName());
        $this->assertNull($jobSector->getSlug());
        $this->assertNull($jobSector->getLanguage());
        $this->assertNull($jobSector->getHidden());
        $this->assertNull($jobSector->getLanguageNeutralId());
    }

    public function testId() {
        $jobSector = new JobSector();

        $this->assertNull($jobSector->getId());
        $jobSector->setId(1);
        $this->assertEquals(1, $jobSector->getId());
        $jobSector->setId(99999);
        $this->assertEquals(99999, $jobSector->getId());
        $jobSector->setId(null);
        $this->assertNull($jobSector->getId());
    }

    public function testName() {
        $jobSector = new JobSector();

        $this->assertNull($jobSector->getName());
        $jobSector->setName("test");
        $this->assertEquals("test", $jobSector->getName());
        $jobSector->setName(null);
        $this->assertNull($jobSector->getName());
    }

    public function testSlug() {
        $jobSector = new JobSector();

        $this->assertNull($jobSector->getSlug());
        $jobSector->setSlug("test");
        $this->assertEquals("test", $jobSector->getSlug());
        $jobSector->setSlug(null);
        $this->assertNull($jobSector->getSlug());
    }

    public function testLanguage() {
        $jobSector = new JobSector();

        $this->assertNull($jobSector->getLanguage());
        $jobSector->setLanguage("nl");
        $this->assertEquals("nl", $jobSector->getLanguage());
        $jobSector->setLanguage("en");
        $this->assertEquals("en", $jobSector->getLanguage());
        $jobSector->setLanguage(null);
        $this->assertNull($jobSector->getLanguage());
    }

    public function testHidden() {
        $jobSector = new JobSector();

        $this->assertNull($jobSector->getHidden());
        $jobSector->setHidden(false);
        $this->assertEquals(false, $jobSector->getHidden());
        $jobSector->setHidden(true);
        $this->assertEquals(true, $jobSector->getHidden());
        $jobSector->setHidden(null);
        $this->assertNull($jobSector->getHidden());
    }

    public function testLanguageNeutralId() {
        $jobSector = new JobSector();

        $this->assertNull($jobSector->getLanguageNeutralId());
        $jobSector->setLanguageNeutralId(1);
        $this->assertEquals(1, $jobSector->getLanguageNeutralId());
        $jobSector->setLanguageNeutralId(99999);
        $this->assertEquals(99999, $jobSector->getLanguageNeutralId());
        $jobSector->setLanguageNeutralId(null);
        $this->assertNull($jobSector->getLanguageNeutralId());
    }
}
