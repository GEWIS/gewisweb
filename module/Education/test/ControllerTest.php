<?php

namespace EducationTest;

use ApplicationTest\BaseControllerTest;
use Education\Service\AclService;

class ControllerTest extends BaseControllerTest
{
    protected string $authServiceClassName = AclService::class;
    protected string $authServiceName = 'education_service_acl';

    public function testEducationActionCanBeAccessed(): void
    {
        $this->dispatch('/education');
        $this->assertResponseStatusCode(200);
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
        $this->dispatch('/admin/education/add/course');
        $this->assertResponseStatusCode(200);
    }
}
