<?php

namespace ApplicationTest\Mapper;

use Application\Mapper\BaseMapper;
use ConsoleRunner;
use Laminas\Mvc\Application;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

abstract class BaseMapperTest extends TestCase
{
    protected Application $application;
    protected ServiceManager $serviceManager;
    protected BaseMapper $mapper;

    public function setUp(): void
    {
        $this->application = ConsoleRunner::getApplication();
        $this->serviceManager = $this->application->getServiceManager();
    }

    public function testGetEntityManager(): void
    {
        $this->mapper->getEntityManager();
        $this->expectNotToPerformAssertions();
    }

    public function testFindBy(): void
    {
        $this->mapper->findBy([]);
        $this->expectNotToPerformAssertions();
    }

    public function testFlush(): void
    {
        $this->mapper->flush();
        $this->expectNotToPerformAssertions();
    }

    public function testGetConnection(): void
    {
        $this->mapper->getConnection();
        $this->expectNotToPerformAssertions();
    }

    public function testCount(): void
    {
        $this->mapper->count([]);
        $this->expectNotToPerformAssertions();
    }

    public function testFindAll(): void
    {
        $this->mapper->findAll();
        $this->expectNotToPerformAssertions();
    }
}
