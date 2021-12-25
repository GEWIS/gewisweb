<?php

namespace ActivityTest;

use Activity\Service\AclService;
use ApplicationTest\BaseControllerTest;

class ControllerTest extends BaseControllerTest
{
    protected string $authServiceClassName = AclService::class;
    protected string $authServiceName = 'activity_service_acl';

    public function testActivityActionCanBeAccessed(): void
    {
        $this->dispatch('/activity');
        $this->assertResponseStatusCode(200);
    }

    public function testActivityArchiveActionCanBeAccessed(): void
    {
        $this->dispatch('/activity/archive');
        $this->assertResponseStatusCode(200);
    }

    public function testActivityCareerActionCanBeAccessed(): void
    {
        $this->dispatch('/activity/career');
        $this->assertResponseStatusCode(200);
    }

    public function testActivityMyActionCanBeAccessedAsUser(): void
    {
        $this->setUpWithRole();
        $this->dispatch('/activity/my');
        $this->assertResponseStatusCode(200);
    }

    public function testActivityCreateActionCanBeAccessedAsAdmin(): void
    {
        $this->setUpWithRole('admin');
        $this->dispatch('/activity/create');
        $this->assertResponseStatusCode(200);
    }

    public function testAdminActivityActionCanBeAccessedAsAdmin(): void
    {
        $this->setUpWithRole('admin');
        $this->dispatch('/admin/activity');
        $this->assertResponseStatusCode(200);
    }

    public function testAdminCategoriesActionCanBeAccessedAsAdmin(): void
    {
        $this->setUpWithRole('admin');
        $this->dispatch('/admin/activity/categories');
        $this->assertResponseStatusCode(200);
    }

    public function testAdminCalendarActionCanBeAccessedAsAdmin(): void
    {
        $this->setUpWithRole('admin');
        $this->dispatch('/admin/activity/calendar');
        $this->assertResponseStatusCode(200);
    }
}
