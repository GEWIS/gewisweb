<?php

namespace User\Mapper;


use User\Model\CompanyUser;
use Zend\ServiceManager\ServiceManager;

class CompanyTest extends \PHPUnit_Framework_TestCase
{
    protected $companyService;

    protected $sm;

    protected $em;

    public function setUp()
    {
        $this->sm = new ServiceManager();

        $this->sm->setInvokableClass('user_service_company', 'User\Service\Company');

        $this->sm->setService('translator', new \Zend\I18n\Translator\Translator());
        $this->sm->setService('decision_acl', new \Zend\Permissions\Acl\Acl());
        $this->sm->setService('user_role', 'guest');
        $mockCompanyMapper = $this->getMockBuilder('User\Mapper\Company')
            ->disableOriginalConstructor()
            ->getMock();
        $this->sm->setService('user_mapper_company', $mockCompanyMapper);

        $this->companyService = $this->sm->get('user_service_company');
        $this->companyService->setServiceManager($this->sm);
    }

    public function testFindById() {
        $companyUser = new CompanyUser();

        $companyUser->setId(1);
        $companyUser->setContactEmail("test@email.com");
        $companyUser->setPassword("password");

        $this->assertNull($this->companyService->getCompanyMapper()->findByLogin("test@email.com"));

    }
}
