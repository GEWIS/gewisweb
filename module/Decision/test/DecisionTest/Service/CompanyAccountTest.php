<?php

namespace DecisionTest\Service;

use PHPUnit_Framework_TestCase;
use Zend\ServiceManager\ServiceManager;

class CompanyAccountTest extends PHPUnit_Framework_TestCase
{

    protected $companyAccount;

    protected $sm;

    function test(){
        $this->assertEquals("hey", "hey", "actual value is not equals to expected");
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/_files/guestbook-seed.xml');
    }

    public function getConnection()
    {
        $pdo = new PDO('sqlite::memory:');
        return $this->createDefaultDBConnection($pdo, ':memory:');
    }

    function setup(){
        $this->sm = new ServiceManager();
        $this->sm->setInvokableClass('decision_service_CompanyAccount', 'Decision\Service\CompanyAccount');

        $this->sm->setService('decision_acl', new \Zend\Permissions\Acl\Acl());
        $this->sm->setService('user_role', 'guest');

        $mapperMock = $this->getMockBuilder('Decision\Mapper\CompanyAccount')
            ->disableOriginalConstructor()
            ->getMock();
        $this->sm->setService('decision_mapper_companyAccount', $mapperMock);

        $this->organ = $this->sm->get('decision_service_CompanyAccount');
        $this->organ->setServiceManager($this->sm);

        $this->sm->setAllowOverride(true);
    }


    public function testGetActiveVacancies()
    {
        $mock = $this->sm->get('decision_mapper_companyAccount');


        $this->assertEmpty($this->organ->getActiveVacancies(3));
    }

}
