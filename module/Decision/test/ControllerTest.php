<?php

namespace DecisionTest;

use ApplicationTest\BaseControllerTest;

class ControllerTest extends BaseControllerTest
{
    public function testMemberActionCanBeAccessedAsUser(): void
    {
        $this->setUpWithRole();
        $this->dispatch('/member');
        $this->assertResponseStatusCode(200);
    }

    public function testMemberSearchActionCanBeAccessedAsUser(): void
    {
        $this->setUpWithRole();
        $this->dispatch('/member/search');
        $this->assertResponseStatusCode(200);
    }

    public function testMemberSearchQueryActionCanBeAccessedAsUser(): void
    {
        $this->setUpWithRole();
        $this->dispatch('/member/search?q=web');
        $this->assertResponseStatusCode(200);
    }

    public function testDecisionSearchActionCanBeAccessedAsUser(): void
    {
        $this->setUpWithRole();
        $this->dispatch(
            '/decision/search',
            'POST',
            ['query' => 'web']
        );
        $this->assertResponseStatusCode(200);
    }

    public function testAuthorizationsActionCanBeAccessedAsUser(): void
    {
        $this->setUpWithRole();
        $this->dispatch('/decision/authorizations');
        $this->assertResponseStatusCode(200);
    }

    public function testAdminDecisionNotesActionCanBeAccessedAsAdmin(): void
    {
        $this->setUpWithRole('admin');
        $this->dispatch('/admin/decision/notes');
        $this->assertResponseStatusCode(200);
    }

    public function testAdminDecisionDocumentActionCanBeAccessedAsAdmin(): void
    {
        $this->setUpWithRole('admin');
        $this->dispatch('/admin/decision/document');
        $this->assertResponseStatusCode(200);
    }

    public function testAdminDecisionAuthorizationsActionCanBeAccessedAsAdmin(): void
    {
        $this->setUpWithRole('admin');
        $this->dispatch('/admin/decision/authorizations');
        $this->assertResponseStatusCode(200);
    }

    public function testAdminOrganActionCanBeAccessedAsAdmin(): void
    {
        $this->setUpWithRole('admin');
        $this->dispatch('/admin/organ');
        $this->assertResponseStatusCode(200);
    }
}
