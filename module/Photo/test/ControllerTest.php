<?php

namespace PhotoTest;

use ApplicationTest\BaseControllerTest;

class ControllerTest extends BaseControllerTest
{
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
