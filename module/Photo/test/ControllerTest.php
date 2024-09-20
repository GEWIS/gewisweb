<?php

declare(strict_types=1);

namespace PhotoTest;

use ApplicationTest\BaseControllerTrait;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class ControllerTest extends AbstractHttpControllerTestCase
{
    use BaseControllerTrait;

    public function testPhotoActionUnauthenticated(): void
    {
        $this->dispatch('/photo');
        $this->assertResponseStatusCode(401);
    }

    public function testPhotoActionCanBeAccessedAsUser(): void
    {
        $this->setUpWithRole();
        $this->dispatch('/photo');
        $this->assertResponseStatusCode(200);
    }

    public function testPhotoActionCannotBeAccessedAsCompanyUser(): void
    {
        $this->setUpWithRole('company');
        $this->dispatch('/photo');
        $this->assertResponseStatusCode(403);
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
