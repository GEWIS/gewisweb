<?php

namespace User\Model;


use Company\Model\Company;
use Zend\ServiceManager\ServiceManager;

class NewCompanyTest extends \PHPUnit_Framework_TestCase
{
    protected $companyService;

    protected $sm;

    public function setUp()
    {
        $this->sm = new ServiceManager();

        $this->sm->setInvokableClass('user_service_company', 'User\Service\Company');
        $mapperMock = $this->getMockBuilder('User\Mapper\Company')
            ->disableOriginalConstructor()
            ->getMock();
        $this->sm->setService('user_mapper_company', $mapperMock);

        $this->companyService = $this->sm->get('user_service_company');
        $this->companyService->setServiceManager($this->sm);
    }

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
