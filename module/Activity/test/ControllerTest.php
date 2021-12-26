<?php

namespace ActivityTest;

use ApplicationTest\BaseControllerTest;

class ControllerTest extends BaseControllerTest
{
    public function testActivityActionCanBeAccessed(): void
    {
        $this->dispatch('/activity');
        $this->assertResponseStatusCode(200);
    }

    public function testActivityActionCanBeAccessedView(): void
    {
        $this->dispatch('/activity/view/1');
        $this->assertNotResponseStatusCode(500);
    }

    public function testActivityActionCanBeAccessedViewAsUser(): void
    {
        $this->setUpWithRole();
        $this->dispatch('/activity/view/1');
        $this->assertNotResponseStatusCode(500);
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
