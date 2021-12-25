<?php

namespace CompanyTest;

use ApplicationTest\BaseControllerTest;
use Company\Service\AclService;

class ControllerTest extends BaseControllerTest
{
    protected string $authServiceClassName = AclService::class;
    protected string $authServiceName = 'company_service_acl';

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
