<?php

namespace User\Model;


class CompanyUserTest extends \PHPUnit_Framework_TestCase
{
    public function testCompanyUserInitialState()
    {
        $companyUser = new CompanyUser();

        $this->assertNull($companyUser->getContactEmail());
        $this->assertNull($companyUser->getPassword());

        $this->assertEquals('company_user_', $companyUser->getRoleId());
        $this->assertEquals('companyUser', $companyUser->getResourceId());
    }

}
