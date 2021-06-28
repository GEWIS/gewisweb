<?php

namespace User\Service;


use Zend\ServiceManager\ServiceManager;

class CompanyEmailTest extends \PHPUnit_Framework_TestCase
{
    protected $companyService;

    protected $sm;

    /**
     * Construct a company service with mock objects.
     */
    public function setUp()
    {
        $this->sm = new ServiceManager();

        $this->sm->setInvokableClass('user_service_companyemail', 'User\Service\CompanyEmail');

        $this->sm->setService('translator', new \Zend\I18n\Translator\Translator());
        $this->sm->setService('decision_acl', new \Zend\Permissions\Acl\Acl());
        $this->sm->setService('user_role', 'guest');
        $mapperMock = $this->getMockBuilder('User\Mapper\Company')
            ->disableOriginalConstructor()
            ->getMock();
        $this->sm->setService('user_mapper_company', $mapperMock);


        $emailService = $this->getMockBuilder('User\Service\Email')
            ->disableOriginalConstructor()
            ->getMock();
        $this->sm->setService('user_mail_transport', $emailService);

//        user_mail_transport
//        return $this->sm->get('view_manager')->getRenderer();


        $this->companyService = $this->sm->get('user_service_companyemail');
        $this->companyService->setServiceManager($this->sm);
    }

}
