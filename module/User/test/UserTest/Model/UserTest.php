<?php

namespace UserTest\Model;

use User\Model\User;
use PHPUnit_Framework_TestCase;

class UserTest extends PHPUnit_Framework_TestCase
{

    public function testUserInitialState()
    {
        $user = new User();

        $this->assertNull($user->getLidnr());
        $this->assertNull($user->getEmail());
        $this->assertNull($user->getPassword());
        $this->assertNull($user->getMember());
        $this->assertEmpty($user->getRoleNames());
        $this->assertEquals('user_', $user->getRoleId());
        $this->assertEquals('user', $user->getResourceId());

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $user->getRoles());
        $this->assertEquals(0, count($user->getRoles()));
    }
}
