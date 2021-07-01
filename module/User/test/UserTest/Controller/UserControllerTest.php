<?php

namespace UserTest\Controller;


use PHPUnit_Framework_TestCase;
use User\Service\CompanyEmail;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ArrayUtils;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;


// Attempted Zend testing, Does not work
class UserControllerTest extends AbstractHttpControllerTestCase
{

    protected $sm;

    protected $companyEmailTable;

    public function configureServiceManager(ServiceManager $services)
    {
        $services->setAllowOverride(true);

        $services->setService('config', $this->updateConfig($services->get('config')));
        $services->setService(CompanyEmail::class, $this->mockEmailTable()->reveal());

        $services->setAllowOverride(false);
    }

    public function updateConfig($config)
    {
        $config['db'] = [];
        return $config;
    }

    public function mockEmailTable()
    {
        $this->companyEmailTable = $this->prophesize(CompanyEmail::class);
        return $this->companyEmailTable;
    }




    protected function setUp()
    {
        // The module configuration should still be applicable for tests.
        // You can override configuration here with test case specific values,
        // such as sample view templates, path stacks, module_listener_options,
        // etc.
        $configOverrides = [];

        $this->setApplicationConfig(ArrayUtils::merge(
            include getcwd() . '/config/application.config.development.php',
            $configOverrides
        ));

        parent::setUp();

        $this->configureServiceManager($this->getApplicationServiceLocator());
    }


//    public function setUp()
//    {
//        $this->setApplicationConfig(
//            include getcwd() . '/config/application.config.development.php'
//        );
//        parent::setUp();
//    }

    public function testDispatch() {
//        $this->dispatch('/user');
    }
}
