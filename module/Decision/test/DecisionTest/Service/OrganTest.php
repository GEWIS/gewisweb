<?php

namespace DecisionTest\Service;

use PHPUnit_Framework_TestCase;
use User\Permissions\NotAllowedException;
use Zend\I18n\Translator\Translator;
use Zend\Permissions\Acl\Acl;
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

        $this->sm->setService('translator', new Translator());
        $this->sm->setService('decision_acl', new Acl());
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
        $acl->addRole('user', ['guest']);
        $acl->addResource('organ');
        $acl->allow('user', 'organ', 'view');
        $acl->allow('user', 'organ', 'list');
    }

    /**
     * @expectedException NotAllowedException
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
            ->will($this->returnValue([]));

        $this->sm->setService('user_role', 'user');

        $this->assertEmpty($this->organ->getOrgans());
    }
}
