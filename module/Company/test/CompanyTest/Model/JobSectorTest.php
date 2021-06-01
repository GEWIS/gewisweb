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
}
