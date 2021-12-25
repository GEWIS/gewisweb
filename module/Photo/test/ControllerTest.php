<?php

namespace PhotoTest;

use ApplicationTest\BaseControllerTest;
use Photo\Service\AclService;

class ControllerTest extends BaseControllerTest
{
    protected string $authServiceClassName = AclService::class;
    protected string $authServiceName = 'photo_service_acl';

    public function testPhotoActionIsForbidden(): void
    {
        $this->dispatch('/photo');
        $this->assertResponseStatusCode(403);
    }

    public function testPhotoActionCanBeAccessedAsUser(): void
    {
        $this->setUpWithRole();
        $this->dispatch('/photo');
        $this->assertResponseStatusCode(200);
    }

    public function testPhotoWeeklyActionCanBeAccessedAsUser(): void
    {
        $this->setUpWithRole();
        $this->dispatch('/photo/weekly');
        $this->assertResponseStatusCode(200);
    }

    public function testAdminPhotoActionCanBeAccessedAsAdmin(): void
    {
        $this->setUpWithRole('admin');
        $this->dispatch('/admin/photo');
        $this->assertResponseStatusCode(200);
    }
}
