<?php

namespace UserTest;

use ApplicationTest\BaseControllerTest;

class ControllerTest extends BaseControllerTest
{
    public function testUserActionCanBeAccessed(): void
    {
        $this->dispatch('/user');
        $this->assertResponseStatusCode(200);
    }

    public function testUserRegisterActionCanBeAccessed(): void
    {
        $this->dispatch('/user/register');
        $this->assertResponseStatusCode(200);
    }

    public function testUserResetActionCanBeAccessed(): void
    {
        $this->dispatch('/user/reset');
        $this->assertResponseStatusCode(200);
    }

    public function testUserLogoutActionDoesRedirect(): void
    {
        $this->dispatch('/user/logout');
        $this->assertResponseStatusCode(302);
    }
}
