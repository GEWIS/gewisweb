<?php

declare(strict_types=1);

namespace EducationTest;

use ApplicationTest\BaseControllerTrait;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class ControllerTest extends AbstractHttpControllerTestCase
{
    use BaseControllerTrait;

    public function testEducationActionCanBeAccessed(): void
    {
        $this->dispatch('/education');
        $this->assertResponseStatusCode(200);
    }

    public function testEducationQueryActionCanBeAccessed(): void
    {
        $this->dispatch('/education?query=web');
        $this->assertResponseStatusCode(200);
    }

    public function testEducationCourseActionCanBeAccessed(): void
    {
        $this->dispatch('/education/course/2IPE0');
        $this->assertNotResponseStatusCode(500);
    }

    public function testEducationCourseActionCanBeAccessedAsUser(): void
    {
        $this->setUpWithRole();
        $this->dispatch('/education/course/2IPE0');
        $this->assertNotResponseStatusCode(500);
    }

    public function testEducationCourseDownloadActionCanBeAccessed(): void
    {
        $this->dispatch('/education/course/2IPE0/download/1');
        $this->assertNotResponseStatusCode(500);
    }

    public function testEducationCourseDownloadActionCanBeAccessedAsUser(): void
    {
        $this->setUpWithRole();
        $this->dispatch('/education/course/2IPE0/download/1');
        $this->assertNotResponseStatusCode(500);
    }

    public function testAdminEducationExamActionCanBeAccessedAsAdmin(): void
    {
        $this->setUpWithRole('admin');
        $this->dispatch('/admin/education/bulk/exam');
        $this->assertResponseStatusCode(200);
    }

    public function testAdminEducationSummaryActionCanBeAccessedAsAdmin(): void
    {
        $this->setUpWithRole('admin');
        $this->dispatch('/admin/education/bulk/summary');
        $this->assertResponseStatusCode(200);
    }

    public function testAdminEducationCourseActionCanBeAccessedAsAdmin(): void
    {
        $this->setUpWithRole('admin');
        $this->dispatch('/admin/education/course/add');
        $this->assertResponseStatusCode(200);
    }
}
