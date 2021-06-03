<?php

namespace UserTest\Controller;


use PHPUnit_Framework_TestCase;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class UserControllerTest extends AbstractHttpControllerTestCase
{

    public function setUp()
    {
        $this->setApplicationConfig(
            include getcwd() . '/config/application.config.development.php'
        );
        parent::setUp();
    }

    public function testDispatch() {
//        $this->dispatch('/user');
    }
}
