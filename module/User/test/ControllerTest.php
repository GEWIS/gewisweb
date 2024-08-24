<?php

declare(strict_types=1);

namespace UserTest;

use ApplicationTest\BaseControllerTrait;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class ControllerTest extends AbstractHttpControllerTestCase
{
    use BaseControllerTrait;

    public function testCompanyLoginActionCanBeAccessed(): void
    {
        $this->dispatch('/user/login/member');
        $this->assertResponseStatusCode(200);
    }

    public function testMemberLoginActionCanBeAccessed(): void
    {
        $this->dispatch('/user/login/member');
        $this->assertResponseStatusCode(200);
    }

    public function testUserRegisterActionCanBeAccessed(): void
    {
        $this->dispatch('/user/activate');
        $this->assertResponseStatusCode(200);
    }

    public function testCompanyUserResetActionCanBeAccessed(): void
    {
        $this->dispatch('/user/password/reset/company');
        $this->assertResponseStatusCode(200);
    }

    public function testUserResetActionCanBeAccessed(): void
    {
        $this->dispatch('/user/password/reset/member');
        $this->assertResponseStatusCode(200);
    }

    public function testUserLogoutActionDoesRedirect(): void
    {
        $this->dispatch('/user/logout');
        $this->assertResponseStatusCode(302);
    }

    public function testAdminUserApiActionCanBeAccessedAsAdmin(): void
    {
        $this->setUpWithRole('admin');
        $this->dispatch('/admin/user/api');
        $this->assertResponseStatusCode(200);
    }
}
