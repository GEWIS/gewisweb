<?php

namespace UserTest\Service;

use Company\Model\Company;
use PHPUnit_Framework_TestCase;
use User\Form\CompanyLogin;
use User\Model\CompanyUser;
use User\Model\NewCompany;
use User\Permissions\NotAllowedException;
use Zend\ServiceManager\ServiceManager;

class CompanyTest extends PHPUnit_Framework_TestCase
{
    protected $companyService;

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

        $authStorage = $this->getMockBuilder('User\Authentication\CompanyStorage\CompanySession')
            ->disableOriginalConstructor()
            ->getMock();
        $this->sm->setService('company_auth_storage', $authStorage);

        $authService = $this->getMockBuilder('User\Authentication\CompanyAuthenticationService')
            ->disableOriginalConstructor()
            ->getMock();
        $this->sm->setService('company_auth_service', $authService);

        $emailService = $this->getMockBuilder('User\Service\Email')
            ->disableOriginalConstructor()
            ->getMock();
        $this->sm->setService('user_service_email', $emailService);

        $companyLoginForm = $this->getMockBuilder('User\Form\CompanyLogin')
            ->disableOriginalConstructor()
            ->getMock();
        $this->sm->setService('user_form_companylogin', $companyLoginForm);

        $loginAttemptMapper = $this->getMockBuilder('User\Mapper\LoginAttempt')
            ->disableOriginalConstructor()
            ->getMock();
        $this->sm->setService('user_mapper_loginattempt', $loginAttemptMapper);

        $passwordForm = $this->getMockBuilder('User\Form\Password')
            ->disableOriginalConstructor()
            ->getMock();
        $this->sm->setService('user_form_password', $passwordForm);


        $this->companyService = $this->sm->get('user_service_company');
        $this->companyService->setServiceManager($this->sm);
    }




    public function testCompanyServiceInstance() {
        $this->assertInstanceOf('User\Service\Company', $this->companyService);
    }

    public function testCompanyLoginFormInstance() {
        $this->assertInstanceOf('User\Form\CompanyLogin', $this->companyService->getCompanyLoginForm());
    }

    public function testEmailServiceInstance() {
        $this->assertInstanceOf('User\Service\Email', $this->companyService->getEmailService());
    }

    public function testAuthStorageInstance() {
        $this->assertInstanceOf('User\Authentication\CompanyStorage\CompanySession', $this->companyService->getAuthStorage());
    }

    public function testLoginAttemptMapperInstance() {
        $this->assertInstanceOf('User\Mapper\LoginAttempt', $this->companyService->getLoginAttemptMapper());
    }

//    public function testPasswordFormInstance() {
//        $this->assertInstanceOf('User\Form\Password', $this->companyService->getPasswordForm());
//    }

    public function testHasIdentityNull() {
        $this->assertNull($this->companyService->hasIdentity());
    }

    public function testGetIdentityNull() {
        $this->expectException(NotAllowedException::class);
        $this->companyService->getIdentity();
    }

    public function testCompanyLogin() {
        $companyAccount = new Company();
        $companyAccount->setContactEmail("test@email.com");
        $companyAccount->setId(1);

        $companyUser = new CompanyUser();
        $companyUser->setId(1);
        $companyUser->setContactEmail("test@email.com");
        $companyUser->setPassword("testPassword");
        $companyUser->setCompanyAccount($companyAccount);

        $data = [
            "contactEmail" => $companyUser->getContactEmail(),
            "password" => $companyUser->getPassword(),
            "remember" => 1,
            "redirect" => ["security" => "35139902b68a648f2b621c85193c1c49-7032e7a2e6c23a493abc56f832e51ba4"],
            "submit" => "Login",
        ];

        $this->assertInstanceOf('User\Model\CompanyUser', $this->companyService->companyLogin($data));
    }

    public function testFormValid() {
//        $form = $this->companyService->getCompanyLoginForm();
        $form = new CompanyLogin($this->sm->get('translator'));

        $data = [
            "login" => "test@email.com",
            "password" => "password",
            "remember" => 1,
        ];

//        print_r($data);
        $form->setData($data);
        $this->assertTrue($form->isValid());
        $this->assertNotNull($form);
//        print_r($form);
//        print_r($form->getData());
    }

    public function testGenerateCode() {
        // code not null
        $this->assertNotNull($this->companyService->generateCode());
        // code of 20 characters
        $this->assertEquals(20, strlen($this->companyService->generateCode()));
    }


}
