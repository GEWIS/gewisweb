<?php

namespace ApplicationTest\Mapper;

use Application\Mapper\BaseMapper;
use ApplicationTest\TestConfigProvider;
use Doctrine\ORM\{
    EntityManager,
    EntityNotFoundException,
};
use Laminas\Mvc\Application;
use Laminas\Mvc\Service\ServiceManagerConfig;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use RuntimeException;

abstract class BaseMapperTest extends TestCase
{
    protected array $applicationConfig;
    protected ?Application $application = null;
    protected ServiceManager $serviceManager;
    protected EntityManager $entityManager;
    protected BaseMapper $mapper;
    protected object $object;

    public function setUp(): void
    {
        $this->applicationConfig = TestConfigProvider::getConfig();
        $this->getApplication();
        $this->entityManager = $this->serviceManager->get('doctrine.entitymanager.orm_default');
    }

    protected function getId(object $object): mixed
    {
        throw new RuntimeException('Not implemented');
    }

    public function getApplication(): Application
    {
        if ($this->application) {
            return $this->application;
        }

        $appConfig = $this->applicationConfig;

        $this->serviceManager = $this->initServiceManager($appConfig);

        $this->serviceManager->setAllowOverride(true);
        TestConfigProvider::overrideConfig($this->serviceManager);
        $this->setUpMockedServices();
        $this->serviceManager->setAllowOverride(false);

        $this->application = $this->bootstrapApplication($this->serviceManager, $appConfig);

        $events = $this->application->getEventManager();
        $this->application->getServiceManager()->get('SendResponseListener')->detach($events);

        return $this->application;
    }

    /**
     * Variation of {@link Application::init} but without initial bootstrapping.
     */
    private static function initServiceManager(array $configuration = []): ServiceManager
    {
        // Prepare the service manager
        $smConfig = $configuration['service_manager'] ?? [];
        $smConfig = new ServiceManagerConfig($smConfig);

        $serviceManager = new ServiceManager();
        $smConfig->configureServiceManager($serviceManager);
        $serviceManager->setService('ApplicationConfig', $configuration);

        // Load modules
        $serviceManager->get('ModuleManager')->loadModules();

        return $serviceManager;
    }

    protected function setUpMockedServices(): void
    {
    }

    private function bootstrapApplication(
        ServiceManager $serviceManager,
        array $configuration = [],
    ): Application {
        // Prepare list of listeners to bootstrap
        $listenersFromAppConfig = $configuration['listeners'] ?? [];
        $config = $serviceManager->get('config');
        $listenersFromConfigService = $config['listeners'] ?? [];

        $listeners = array_unique(array_merge($listenersFromConfigService, $listenersFromAppConfig));
        return $serviceManager->get('Application')->bootstrap($listeners);
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

    public function testFindOneBy(): void
    {
        $this->mapper->findOneBy([]);
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

    public function testPersist(): void
    {
        $this->mapper->persist($this->object);
        $this->expectNotToPerformAssertions();
    }

    public function testPersistMultiple(): void
    {
        $this->mapper->persistMultiple([$this->object]);
        $this->expectNotToPerformAssertions();
    }

    public function testRemove(): void
    {
        $this->mapper->remove($this->object);
        $this->expectNotToPerformAssertions();
    }

    public function testRemoveMultiple(): void
    {
        $this->mapper->removeMultiple([$this->object]);
        $this->expectNotToPerformAssertions();
    }

    public function testRemoveById(): void
    {
        try {
            $id = $this->getId($this->object);
            $this->entityManager->persist($this->object);
            $this->mapper->removeById($id);
            $this->expectNotToPerformAssertions();
        } catch (RuntimeException $e) {
            if ($e->getMessage() !== 'Not implemented') {
                $this->addWarning($e->getMessage());
                $this->addWarning($e->getTraceAsString());
                $this->fail('testRemoveById threw an unexpected exception.');
            } else {
                $this->expectNotToPerformAssertions();
            }
        }
    }

    public function testRemoveByIdNotFound(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->mapper->removeById(0);
    }

    public function testDetach(): void
    {
        $this->mapper->detach($this->object);
        $this->expectNotToPerformAssertions();
    }
}
