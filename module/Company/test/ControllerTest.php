<?php

declare(strict_types=1);

namespace CompanyTest;

use ApplicationTest\BaseControllerTrait;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class ControllerTest extends AbstractHttpControllerTestCase
{
    use BaseControllerTrait;

    public function testCareerActionCanBeAccessed(): void
    {
        $this->dispatch('/career');
        $this->assertResponseStatusCode(200);
    }

    public function testCareerVacanciesActionCanBeAccessed(): void
    {
        $this->dispatch('/career/vacancies');
        $this->assertNotResponseStatusCode(500);
    }

    public function testCareerCompanyActionCanBeAccessed(): void
    {
        $this->dispatch('/career/company/asml');
        $this->assertNotResponseStatusCode(500);
    }

    public function testCareerJobActionCanBeAccessed(): void
    {
        $this->dispatch('/career/company/asml/vacancies/web_developer');
        $this->assertNotResponseStatusCode(500);
    }

    public function testAdminCareerActionCanBeAccessedAsAdmin(): void
    {
        $this->setUpWithRole('admin');
        $this->dispatch('/admin/career');
        $this->assertResponseStatusCode(200);
    }

    public function testCompanyAccountSelfActionCannotBeAccessed(): void
    {
        $this->dispatch('/company/self');
        $this->assertResponseStatusCode(403);
    }

    public function testCompanyAccountSelfActionCanBeAccessedAsCompany(): void
    {
        $this->setUpWithRole('company');
        $this->dispatch('/company/self');
        $this->assertResponseStatusCode(200);
    }
}
