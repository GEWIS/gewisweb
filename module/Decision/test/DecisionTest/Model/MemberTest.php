<?php

namespace DecisionTest\Model;

use Decision\Model\Member;
use PHPUnit_Framework_TestCase;

class MemberTest extends PHPUnit_Framework_TestCase
{

    public function testMemberInitialState()
    {
        $member = new Member();

        $this->assertNull($member->getLidnr());
        $this->assertNull($member->getEmail());
        $this->assertNull($member->getLastName());
        $this->assertNull($member->getMiddleName());
        $this->assertNull($member->getFirstName());
    }
}
