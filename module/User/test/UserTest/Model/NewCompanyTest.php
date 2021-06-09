<?php

namespace User\Model;


use Company\Model\Company;
use Zend\ServiceManager\ServiceManager;

class NewCompanyTest extends \PHPUnit_Framework_TestCase
{
    public function testEmptyNewCompanyInitialState()
    {
        $newCompany = new NewCompany();
        $this->assertNull($newCompany->getContactEmail());
        $this->assertNull($newCompany->getId());
    }

    public function testCreatedNewCompanyInitialState()
    {
        $companyAccount = new Company();
        $companyAccount->setContactEmail("test@email.com");
        $companyAccount->setId(1);

        $newCompany = new NewCompany($companyAccount);
        $this->assertEquals("test@email.com", $newCompany->getContactEmail());
        $this->assertEquals(1, $newCompany->getId());
    }
}
