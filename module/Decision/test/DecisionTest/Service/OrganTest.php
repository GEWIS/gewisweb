<?php

namespace DecisionTest\Service;

use PHPUnit_Framework_TestCase;
use Zend\ServiceManager\ServiceManager;

class OrganTest extends PHPUnit_Framework_TestCase
{

    protected $organ;

    protected $sm;

    /**
     * Construct an organ service with mock objects.
     */
    public function setUp()
    {
        $this->sm = new ServiceManager();

        $this->sm->setInvokableClass('decision_service_organ', 'Decision\Service\Organ');

        $this->sm->setService('translator', new \Zend\I18n\Translator\Translator());
        $this->sm->setService('decision_acl', new \Zend\Permissions\Acl\Acl());
        $this->sm->setService('user_role', 'guest');
        $mapperMock = $this->getMockBuilder('Decision\Mapper\Organ')
            ->disableOriginalConstructor()
            ->getMock();
        $this->sm->setService('decision_mapper_organ', $mapperMock);

        $this->organ = $this->sm->get('decision_service_organ');
        $this->organ->setServiceManager($this->sm);

        $this->sm->setAllowOverride(true);

        // setup ACL
        $acl = $this->sm->get('decision_acl');
        $acl->addRole('guest');
        $acl->addRole('user', array('guest'));
        $acl->addResource('organ');
        $acl->allow('user', 'organ', 'view');
        $acl->allow('user', 'organ', 'list');
    }

    /**
     * @expectedException \User\Permissions\NotAllowedException
     */
    public function testGetOrgansThrowsNotAllowedExceptionOnGuest()
    {
        $this->organ->getOrgans();
    }

    public function testGetOrgans()
    {
        $mock = $this->sm->get('decision_mapper_organ');
        $mock->expects($this->any())
            ->method('findAll')
            ->will($this->returnValue(array()));

        $this->sm->setService('user_role', 'user');

        $this->assertEmpty($this->organ->getOrgans());
    }
}
