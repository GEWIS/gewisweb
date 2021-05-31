<?php

namespace User\Model;


use Company\Model\Company;

class NewCompanyTest extends \PHPUnit_Framework_TestCase
{
    public function testNewCompanyInitialState()
    {
        $company = new Company();
        $company->setContactEmail("test@company.com");
        $company->setId(1);

        $newCompany = new NewCompany($company);

        $this->assertEquals("test@company.com", $newCompany->getContactEmail());
        $this->assertEquals(1, $newCompany->getId());

        $this->assertNotNull($newCompany->getCode());
    }
}
