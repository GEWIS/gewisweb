<?php

namespace ApplicationTest;

use DateTime;
use Decision\Model\Member;
use Doctrine\Common\Collections\ArrayCollection;
use Laminas\Mvc\Application;
use Laminas\Mvc\Service\ServiceManagerConfig;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use User\Authentication\AuthenticationService;
use User\Model\User;
use User\Model\UserRole;
use User\Service\AclService;

abstract class BaseControllerTest extends AbstractHttpControllerTestCase
{
    protected ServiceManager $serviceManager;

    protected MockObject $authService;
    protected MockObject $aclService;

    public function setUp(): void
    {
        $this->setApplicationConfig(
            include './config/application.config.php'
        );

        parent::setUp();
        $this->getApplication();
    }

    protected function setUpMockedServices()
    {
        $this->setUpMockAuthService();
    }

    public function getApplication(): Application
    {
        if ($this->application) {
            return $this->application;
        }

        $appConfig = $this->applicationConfig;

        $this->serviceManager = $this->initServiceManager($appConfig);

        $this->serviceManager->setAllowOverride(true);
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
    private static function initServiceManager($configuration = []): ServiceManager
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

    private function bootstrapApplication($serviceManager, $configuration = []): Application
    {
        // Prepare list of listeners to bootstrap
        $listenersFromAppConfig = $configuration['listeners'] ?? [];
        $config = $serviceManager->get('config');
        $listenersFromConfigService = $config['listeners'] ?? [];

        $listeners = array_unique(array_merge($listenersFromConfigService, $listenersFromAppConfig));
        return $serviceManager->get('Application')->bootstrap($listeners);
    }

    private function setUpMockAuthService(): void
    {
        $storage = $this->serviceManager->get('user_auth_storage');
        $adapter = $this->serviceManager->get('user_auth_adapter');

        $this->authService = $this->getMockBuilder(AuthenticationService::class)
            ->setConstructorArgs([$storage, $adapter])
            ->getMock();

        $this->serviceManager->setService('user_auth_service', $this->authService);
    }

    protected function setUpWithRole(string $role = 'user'): void
    {
        $this->authService->method('getIdentity')->willReturn($this->getMockIdentity($role));

        if ($role != 'guest') {
            $this->authService->method('hasIdentity')->willReturn(true);
        } else {
            $this->authService->method('hasIdentity')->willReturn(false);
        }
    }

    private function getMockIdentity(string $role): ?User
    {
        if ($role === 'guest') {
            return null;
        }

        if ($role !== 'user') {
            $roleModel = new UserRole();
            $roleModel->setRole($role);

            $roles = new ArrayCollection([$roleModel]);
        } else {
            $roles = new ArrayCollection([]);
        }
        $user = $this->getMockUser($roles);

        if (isset($roleModel)) {
            $roleModel->setLidnr($user);
        }

        return $user;
    }

    private function getMockUser(ArrayCollection $roles): User
    {
        $user = new User();
        $user->setLidnr(8000);
        $user->setEmail('web@gewis.nl');
        $user->setPassword('I dont care');
        $user->setRoles($roles);
        $user->setMember($this->getMockMember());

        return $user;
    }

    private function getMockMember(): Member
    {
        $member = new Member();
        $member->setLidnr(8000);
        $member->setEmail('web@gewis.nl');
        $member->setBirth(DateTime::createFromFormat('Y/m/d', '2000/01/01'));
        $member->setGender(Member::GENDER_MALE);
        $member->setInitials('W.C.');
        $member->setFirstName('Web');
        $member->setMiddleName('');
        $member->setLastName('Committee');
        $member->setGeneration(2020);
        $member->setType(Member::TYPE_ORDINARY);
        $member->setExpiration(DateTime::createFromFormat('Y/m/d', '2030/01/01'));
        $member->setChangedOn(DateTime::createFromFormat('Y/m/d', '2020/01/01'));

        return $member;
    }
}
