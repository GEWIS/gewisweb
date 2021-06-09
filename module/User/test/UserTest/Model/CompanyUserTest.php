<?php

namespace User\Model;


use Company\Model\Company;

class CompanyUserTest extends \PHPUnit_Framework_TestCase
{
    public function testCompanyUserInitialState()
    {
        $companyUser = new CompanyUser();

        $this->assertNull($companyUser->getId());
        $this->assertNull($companyUser->getContactEmail());
        $this->assertNull($companyUser->getPassword());

        $this->assertEquals('company_user_', $companyUser->getRoleId());
        $this->assertEquals('companyUser', $companyUser->getResourceId());
        $this->assertEquals(["company_user"], $companyUser->getRoleNames());
    }


    public function testId() {
        $companyUser = new CompanyUser();
        $companyUser->setId(1);
        $this->assertEquals(1, $companyUser->getId());
        $companyUser->setId(999999);
        $this->assertEquals(999999, $companyUser->getId());
        $companyUser->setId(null);
        $this->assertNull($companyUser->getId());
    }

    public function testContactEmail() {
        $companyUser = new CompanyUser();
        $companyUser->setContactEmail("test@email.com");
        $this->assertEquals("test@email.com", $companyUser->getContactEmail());
        $companyUser->setContactEmail(null);
        $this->assertNull($companyUser->getContactEmail());
    }

    public function testPassword() {
        $companyUser = new CompanyUser();
        $companyUser->setPassword("password");
        $this->assertEquals("password", $companyUser->getPassword());
        $companyUser->setPassword("1234");
        $this->assertEquals("1234", $companyUser->getPassword());
        $companyUser->setPassword(1234);
        $this->assertEquals(1234, $companyUser->getPassword());
        $companyUser->setPassword(null);
        $this->assertNull($companyUser->getPassword());
    }

    public function testSessions() {
        $companyUser = new CompanyUser();
        $session = new Session();

        $companyUser->setSessions($session);
        $this->assertEquals($session, $companyUser->getSessions());
        $this->assertInstanceOf('User\Model\Session', $companyUser->getSessions());
        $companyUser->setSessions(null);
        $this->assertNull($companyUser->getSessions());
    }

    public function testCompanyAccount() {
        $companyUser = new CompanyUser();
        $company = new Company();

        $companyUser->setCompanyAccount($company);
        $this->assertEquals($company, $companyUser->getCompanyAccount());
        $this->assertInstanceOf('Company\Model\Company', $companyUser->getCompanyAccount());
        $companyUser->setCompanyAccount(null);
        $this->assertNull($companyUser->getCompanyAccount());
    }

    public function testRoleId() {
        $companyUser = new CompanyUser();

        $companyUser->setId(1);
        $this->assertEquals('company_user_1', $companyUser->getRoleId());
        $companyUser->setId(999999);
        $this->assertEquals('company_user_999999', $companyUser->getRoleId());
        $companyUser->setId(null);
        $this->assertEquals('company_user_', $companyUser->getRoleId());
    }
}
