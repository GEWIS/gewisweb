<?php

namespace UserTest\Authentication;

use ApplicationTest\BaseControllerTest;

class ControllerTest extends BaseControllerTest
{
    public function testUserActionCanBeAccessed()
    {
        $this->dispatch('/user');
        $this->assertResponseStatusCode(200);
    }

    public function testUserRegisterActionCanBeAccessed()
    {
        $this->dispatch('/user/register');
        $this->assertResponseStatusCode(200);
    }

    public function testUserResetActionCanBeAccessed()
    {
        $this->dispatch('/user/reset');
        $this->assertResponseStatusCode(200);
    }

    public function testUserLogoutActionDoesRedirect()
    {
        $this->dispatch('/user/logout');
        $this->assertResponseStatusCode(302);
    }
}
