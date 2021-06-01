<?php

namespace UserTest\Service;

use Company\Model\Company;
use PHPUnit_Framework_TestCase;
use User\Model\CompanyUser;
use User\Model\NewCompany;
use Zend\ServiceManager\ServiceManager;

class CompanyTest extends PHPUnit_Framework_TestCase
{
    protected $company;

    protected $sm;

    /**
     * Construct an organ service with mock objects.
     */
    public function setUp()
    {
        $this->sm = new ServiceManager();

        $this->sm->setInvokableClass('user_service_company', 'User\Service\Company');

        $this->sm->setService('translator', new \Zend\I18n\Translator\Translator());
        $this->sm->setService('decision_acl', new \Zend\Permissions\Acl\Acl());
        $this->sm->setService('user_role', 'guest');
        $mapperMock = $this->getMockBuilder('User\Mapper\Company')
            ->disableOriginalConstructor()
            ->getMock();
        $this->sm->setService('user_mapper_company', $mapperMock);

        $this->company = $this->sm->get('user_service_company');
        $this->company->setServiceManager($this->sm);

        $companyAccount = new Company();
        $companyAccount->setContactEmail("test@email.com");
        $companyAccount->setId(1);

        $newCompany = new NewCompany($companyAccount);

//        $acl = $this->company->getAcl();

//        $companyUser = new CompanyUser();
//        $companyUser->setContactEmail("test@email.com");
//        $companyUser->setPassword("testPassword");
//        $companyUser->setId(1);
    }


    public function testCompanyServiceInstance() {
        $this->assertInstanceOf('User\Service\Company', $this->company);
    }

    public function testGenerateCode() {
        // code not null
        $this->assertNotNull($this->company->generateCode());
        // code of 20 characters
        $this->assertEquals(20, strlen($this->company->generateCode()));
    }


}
