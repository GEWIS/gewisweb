<?php

namespace CompanyTest;

use ApplicationTest\BaseControllerTest;

class ControllerTest extends BaseControllerTest
{
    public function testCareerActionCanBeAccessed(): void
    {
        $this->dispatch('/career');
        $this->assertResponseStatusCode(200);
    }

    public function testAdminCareerActionCanBeAccessedAsAdmin(): void
    {
        $this->setUpWithRole('admin');
        $this->dispatch('/admin/career');
        $this->assertResponseStatusCode(200);
    }
}
