<?php

namespace DecisionTest\Model;

include("module/Decision/src/Decision/Model/Organ.php");

use Decision\Model\Organ;
use PHPUnit_Framework_TestCase;

class OrganTest extends PHPUnit_Framework_TestCase
{
    public function testOrganInitialState()
    {
        $organ = new Organ();

        $this->assertNull($organ->getId());
        $this->assertNull($organ->getAbbr());
        $this->assertNull($organ->getName());
        $this->assertNull($organ->getType());
    }

    public function testOrganSetType()
    {
        $organ = new Organ();

        $organ->setType(Organ::ORGAN_TYPE_FRATERNITY);
        $this->assertEquals(Organ::ORGAN_TYPE_FRATERNITY, $organ->getType());

        $organ->setType(Organ::ORGAN_TYPE_COMMITTEE);
        $this->assertEquals(Organ::ORGAN_TYPE_COMMITTEE, $organ->getType());

        $organ->setType(Organ::ORGAN_TYPE_AVC);
        $this->assertEquals(Organ::ORGAN_TYPE_AVC, $organ->getType());

        $organ->setType(Organ::ORGAN_TYPE_AVW);
        $this->assertEquals(Organ::ORGAN_TYPE_AVW, $organ->getType());

        $organ->setType(Organ::ORGAN_TYPE_KKK);
        $this->assertEquals(Organ::ORGAN_TYPE_KKK, $organ->getType());

        $organ->setType(Organ::ORGAN_TYPE_RVA);
        $this->assertEquals(Organ::ORGAN_TYPE_RVA, $organ->getType());
    }
}
