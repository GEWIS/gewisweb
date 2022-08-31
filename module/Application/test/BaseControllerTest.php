<?php

namespace ApplicationTest;

use DateTime;
use Decision\Model\Enums\MembershipTypes;
use Decision\Model\Member;
use Doctrine\Common\Collections\ArrayCollection;
use Laminas\Mvc\{Application, ApplicationInterface};
use Laminas\Mvc\Service\ServiceManagerConfig;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use User\Authentication\AuthenticationService;
use User\Model\{
    User,
    UserRole,
};

abstract class BaseControllerTest extends AbstractHttpControllerTestCase
{
    protected ServiceManager $serviceManager;

    protected MockObject $authService;
    protected MockObject $aclService;
    protected MockObject $userMapper;
    protected MockObject $memberMapper;

    protected const LIDNR = 8000;
    protected User $user;
    protected Member $member;

    public function setUp(): void
    {
        $this->setApplicationConfig(TestConfigProvider::getConfig());
        parent::setUp();
        $this->getApplication();
    }

    protected function setUpMockedServices(): void
    {
        $this->setUpMockAuthService();
        $this->setUpMockUserMapper();
        $this->setUpMockMemberMapper();
    }

    public function getApplication(): ApplicationInterface
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

    private function setUpMockAuthService(): void
    {
        $storage = $this->serviceManager->get('user_auth_storage');
        $adapter = $this->serviceManager->get('user_auth_adapter');

        $this->authService = $this->getMockBuilder(AuthenticationService::class)
            ->setConstructorArgs([$storage, $adapter])
            ->getMock();

        $this->serviceManager->setService('user_auth_service', $this->authService);
    }

    private function setUpMockUserMapper(): void
    {
        $entityManager = $this->serviceManager->get('doctrine.entitymanager.orm_default');

        $this->userMapper = $this->getMockBuilder(\User\Mapper\User::class)
            ->setConstructorArgs([$entityManager])
            ->enableProxyingToOriginalMethods()
            ->getMock();

        $this->serviceManager->setService('user_mapper_user', $this->userMapper);
    }

    private function setUpMockMemberMapper(): void
    {
        $entityManager = $this->serviceManager->get('doctrine.entitymanager.orm_default');

        $this->memberMapper = $this->getMockBuilder(\Decision\Mapper\Member::class)
            ->setConstructorArgs([$entityManager])
            ->enableProxyingToOriginalMethods()
            ->getMock();

        $this->serviceManager->setService('decision_mapper_member', $this->memberMapper);
    }

    protected function setUpWithRole(string $role = 'user'): void
    {
        $this->authService->method('getIdentity')->willReturn($this->setUpMockIdentity($role));

        if ($role !== 'guest') {
            $this->authService->method('hasIdentity')->willReturn(true);
        } else {
            $this->authService->method('hasIdentity')->willReturn(false);
        }
    }

    private function setUpMockIdentity(string $role): ?User
    {
        if ($role === 'guest') {
            return null;
        }

        if ($role !== 'user') {
            $roleModel = new UserRole();
            $roleModel->setRole($role);

            $roles = new ArrayCollection([$roleModel]);
        } else {
            $roles = new ArrayCollection();
        }

        $this->setUpMockMember();
        $this->setUpMockUser($roles);

        if (isset($roleModel)) {
            $roleModel->setLidnr($this->user);
        }

        $this->userMapper->method('findByLidnr')->willReturnMap([[$this::LIDNR], $this->user]);
        $this->memberMapper->method('findByLidnr')->willReturnMap([[$this::LIDNR], $this->member]);

        return $this->user;
    }

    protected function setUpMockUser(ArrayCollection $roles = new ArrayCollection()): void
    {
        $this->user = new User();
        $this->user->setLidnr($this::LIDNR);
        $this->user->setPassword('I dont care');
        $this->user->setRoles($roles);
        $this->user->setMember($this->member);
    }

    protected function setUpMockMember(): void
    {
        $this->member = new Member();
        $this->member->setLidnr($this::LIDNR);
        $this->member->setEmail('web@gewis.nl');
        $this->member->setBirth(DateTime::createFromFormat('Y/m/d', '2000/01/01'));
        $this->member->setInitials('W.C.');
        $this->member->setFirstName('Web');
        $this->member->setMiddleName('');
        $this->member->setLastName('Committee');
        $this->member->setGeneration(2020);
        $this->member->setType(MembershipTypes::Ordinary);
        $this->member->setMembershipEndsOn(null);
        $this->member->setExpiration(DateTime::createFromFormat('Y/m/d', '2030/01/01'));
        $this->member->setChangedOn(DateTime::createFromFormat('Y/m/d', '2020/01/01'));
    }
}
