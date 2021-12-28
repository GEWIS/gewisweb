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

    public function testPhotoYearActionCanBeAccessedAsUser(): void
    {
        $this->setUpWithRole();
        $this->dispatch('/photo/2020');
        $this->assertNotResponseStatusCode(500);
    }

    public function testPhotoAlbumActionCanBeAccessedAsUser(): void
    {
        $this->setUpWithRole();
        $this->dispatch('/photo/album/1');
        $this->assertNotResponseStatusCode(500);
    }

    public function testPhotoMember1ActionCanBeAccessedAsUser(): void
    {
        $this->setUpWithRole();
        $this->dispatch('/photo/member/1');
        $this->assertNotResponseStatusCode(500);
    }

    public function testPhotoMember8000ActionCanBeAccessedAsUser(): void
    {
        $this->setUpWithRole();
        $this->dispatch('/photo/member/8000');
        $this->assertNotResponseStatusCode(500);
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
