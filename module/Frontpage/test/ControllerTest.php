<?php

declare(strict_types=1);

namespace FrontpageTest;

use ApplicationTest\BaseControllerTest;

class ControllerTest extends BaseControllerTest
{
    /**
     * @psalm-suppress UnevaluatedCode
     */
    public function testIndexActionCanBeAccessed(): void
    {
        $this->markTestSkipped('\Decision\Mapper\Member::findBirthdayMembers() is not supported by SQLite');

        $this->dispatch('/');
        $this->assertResponseStatusCode(200);
    }

    public function testPollHistoryActionCanBeAccessed(): void
    {
        $this->dispatch('/poll/history');
        $this->assertResponseStatusCode(200);
    }

    public function testPollRequestActionIsForbidden(): void
    {
        $this->dispatch('/poll/request');
        $this->assertResponseStatusCode(403);
    }

    public function testPollRequestActionCanBeAccessedAsUser(): void
    {
        $this->setUpWithRole();
        $this->dispatch('/poll/request');
        $this->assertResponseStatusCode(200);
    }

    public function testAssociationCommitteesActionCanBeAccessed(): void
    {
        $this->dispatch('/association/committees');
        $this->assertResponseStatusCode(200);
    }

    public function testAssociationFraternitiesActionCanBeAccessed(): void
    {
        $this->dispatch('/association/fraternities');
        $this->assertResponseStatusCode(200);
    }

    public function testAdminActionCanBeAccessedAsAdmin(): void
    {
        $this->setUpWithRole('admin');
        $this->dispatch('/admin');
        $this->assertResponseStatusCode(200);
    }

    public function testAdminNewsActionCanBeAccessedAsAdmin(): void
    {
        $this->setUpWithRole('admin');
        $this->dispatch('/admin/news');
        $this->assertResponseStatusCode(200);
    }

    public function testAdminPageActionCanBeAccessedAsAdmin(): void
    {
        $this->setUpWithRole('admin');
        $this->dispatch('/admin/page');
        $this->assertResponseStatusCode(200);
    }

    public function testAdminPollActionCanBeAccessedAsAdmin(): void
    {
        $this->setUpWithRole('admin');
        $this->dispatch('/admin/poll');
        $this->assertResponseStatusCode(200);
    }

    public function testPageActionCanBeAccessedAsAdmin(): void
    {
        $this->setUpWithRole('admin');
        $this->dispatch('/somenonexistentpage');
        $this->assertNotResponseStatusCode(500);
    }

    public function testPageAction2CanBeAccessedAsAdmin(): void
    {
        $this->setUpWithRole('admin');
        $this->dispatch('/some/nonexistentpage');
        $this->assertNotResponseStatusCode(500);
    }

    public function testPageAction3CanBeAccessedAsAdmin(): void
    {
        $this->setUpWithRole('admin');
        $this->dispatch('/some/nonexistent/page');
        $this->assertNotResponseStatusCode(500);
    }
}
