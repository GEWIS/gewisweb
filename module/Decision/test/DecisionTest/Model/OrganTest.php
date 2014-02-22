<?php

namespace DecisionTest\Model;

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
        $this->assertEquals(Organ::TYPE_COMMITTEE, $organ->getType());
    }

    public function testOrganSetType()
    {
        $organ = new Organ();

        $organ->setType(Organ::TYPE_FRATERNITY);
        $this->assertEquals(Organ::TYPE_FRATERNITY, $organ->getType());

        $organ->setType(Organ::TYPE_COMMITTEE);
        $this->assertEquals(Organ::TYPE_COMMITTEE, $organ->getType());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testOrganSetTypeThrowsException()
    {
        $organ = new Organ();

        $organ->setType('wrong');
    }
}
