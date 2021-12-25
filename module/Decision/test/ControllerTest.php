<?php

namespace DecisionTest;

use ApplicationTest\BaseControllerTest;
use Decision\Service\AclService;

class ControllerTest extends BaseControllerTest
{
    protected string $authServiceClassName = AclService::class;
    protected string $authServiceName = 'decision_service_acl';

    public function testMemberActionCanBeAccessedAsUser(): void
    {
        $this->setUpWithRole();
        $this->dispatch('/member');
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
