<?php

declare(strict_types=1);

namespace ApplicationTest;

use Company\Mapper\Company as CompanyMapper;
use Company\Model\Company;
use Company\Model\CompanyLocalisedText;
use DateInterval;
use DateTime;
use Decision\Mapper\Member as MemberMapper;
use Decision\Model\Enums\MembershipTypes;
use Decision\Model\Member;
use Doctrine\Common\Collections\ArrayCollection;
use Laminas\Mvc\Application;
use Laminas\Mvc\ApplicationInterface;
use Laminas\Mvc\Service\ServiceManagerConfig;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\MockObject\MockObject;
use User\Authentication\Adapter\CompanyUserAdapter;
use User\Authentication\Adapter\UserAdapter;
use User\Authentication\AuthenticationService;
use User\Authentication\Storage\CompanyUserSession;
use User\Authentication\Storage\UserSession;
use User\Mapper\CompanyUser as CompanyUserMapper;
use User\Mapper\User as UserMapper;
use User\Model\CompanyUser;
use User\Model\Enums\UserRoles;
use User\Model\NewCompanyUser;
use User\Model\User;
use User\Model\UserRole;

use function array_merge;
use function array_unique;

trait BaseControllerTrait
{
    protected ServiceManager $serviceManager;

    protected MockObject $aclService;
    protected MockObject $companyUserAuthService;
    protected MockObject $userAuthService;

    protected MockObject $companyMapper;
    protected MockObject $companyUserMapper;

    protected MockObject $userMapper;
    protected MockObject $memberMapper;

    protected const int LIDNR = 8000;
    protected User $user;
    protected Member $member;

    protected const int COMPANY_ID = 42;
    protected Company $company;
    protected CompanyUser $companyUser;
    protected NewCompanyUser $newCompanyUser;

    public function setUp(): void
    {
        $this->setApplicationConfig(TestConfigProvider::getConfig());

        parent::setUp();

        $this->getApplication();
    }

    protected function setUpMockedServices(): void
    {
        $this->setUpMockCompanyUserAuthService();
        $this->setUpMockCompanyMapper();
        $this->setUpMockCompanyUserMapper();
        $this->setUpMockUserAuthService();
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
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
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

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
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

    private function setUpMockCompanyUserAuthService(): void
    {
        $storage = $this->serviceManager->get(CompanyUserSession::class);
        $adapter = $this->serviceManager->get(CompanyUserAdapter::class);

        $this->companyUserAuthService = $this->getMockBuilder(AuthenticationService::class)
            ->setConstructorArgs([$storage, $adapter])
            ->getMock();

        $this->serviceManager->setService('user_auth_companyUser_service', $this->companyUserAuthService);
    }

    private function setUpMockCompanyMapper(): void
    {
        $entityManager = $this->serviceManager->get('doctrine.entitymanager.orm_default');

        $this->companyMapper = $this->getMockBuilder(CompanyMapper::class)
            ->setConstructorArgs([$entityManager])
            ->getMock();

        $this->serviceManager->setService(CompanyMapper::class, $this->companyMapper);
    }

    private function setUpMockCompanyUserMapper(): void
    {
        $entityManager = $this->serviceManager->get('doctrine.entitymanager.orm_default');

        $this->companyUserMapper = $this->getMockBuilder(CompanyUserMapper::class)
            ->setConstructorArgs([$entityManager])
            ->getMock();

        $this->serviceManager->setService(CompanyUserMapper::class, $this->companyUserMapper);
    }

    private function setUpMockUserAuthService(): void
    {
        $storage = $this->serviceManager->get(UserSession::class);
        $adapter = $this->serviceManager->get(UserAdapter::class);

        $this->userAuthService = $this->getMockBuilder(AuthenticationService::class)
            ->setConstructorArgs([$storage, $adapter])
            ->getMock();

        $this->serviceManager->setService('user_auth_user_service', $this->userAuthService);
    }

    private function setUpMockUserMapper(): void
    {
        $entityManager = $this->serviceManager->get('doctrine.entitymanager.orm_default');

        $this->userMapper = $this->getMockBuilder(UserMapper::class)
            ->setConstructorArgs([$entityManager])
            ->getMock();

        $this->serviceManager->setService(UserMapper::class, $this->userMapper);
    }

    private function setUpMockMemberMapper(): void
    {
        $entityManager = $this->serviceManager->get('doctrine.entitymanager.orm_default');

        $this->memberMapper = $this->getMockBuilder(MemberMapper::class)
            ->setConstructorArgs([$entityManager])
            ->getMock();

        $this->serviceManager->setService(MemberMapper::class, $this->memberMapper);
    }

    protected function setUpWithRole(string $role = 'user'): void
    {
        if ('company' === $role) {
            $this->companyUserAuthService->method('getIdentity')->willReturn($this->setUpMockIdentity($role));
            $this->companyUserAuthService->method('hasIdentity')->willReturn(true);
        } else {
            $this->userAuthService->method('getIdentity')->willReturn($this->setUpMockIdentity($role));

            if ('guest' !== $role) {
                $this->userAuthService->method('hasIdentity')->willReturn(true);
            } else {
                $this->userAuthService->method('hasIdentity')->willReturn(false);
            }
        }
    }

    private function setUpMockIdentity(string $role): CompanyUser|User|null
    {
        if ('guest' === $role) {
            return null;
        }

        if ('company' === $role) {
            $this->setUpMockCompany();
            $this->setUpMockNewCompanyUser();
            $this->setUpMockCompanyUser();

            $this->companyMapper->method('find')->willReturnMap([[$this::COMPANY_ID], [$this->company]]);
            $this->companyUserMapper->method('find')->willReturnMap([[$this::COMPANY_ID], [$this->companyUser]]);

            return $this->companyUser;
        }

        if ('user' !== $role) {
            $roleModel = new UserRole();
            $roleModel->setRole(UserRoles::from($role));
            $roleModel->setExpiration((new DateTime('now'))->add(new DateInterval('P1D')));

            $roles = new ArrayCollection([$roleModel]);
        } else {
            $roles = new ArrayCollection();
        }

        $this->setUpMockMember();
        $this->setUpMockUser($roles);

        if (isset($roleModel)) {
            $roleModel->setLidnr($this->user);
        }

        $this->userMapper->method('find')->willReturnMap([[$this::LIDNR], [$this->user]]);
        $this->memberMapper->method('findByLidnr')->willReturnMap([[$this::LIDNR], [$this->member]]);

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

    protected function setUpMockCompany(): void
    {
        $this->company = new Company();
        $this->company->setId($this::COMPANY_ID);
        $this->company->setName('GEWISER');
        $this->company->setSlugName('gewiser');
        $this->company->setRepresentativeName('Web Committee');
        $this->company->setRepresentativeEmail('web@gewis.nl');
        $this->company->setContactName(null);
        $this->company->setContactAddress(null);
        $this->company->setContactEmail(null);
        $this->company->setContactPhone(null);
        $this->company->setSlogan(new CompanyLocalisedText('More than just GEWIS', null));
        $this->company->setLogo(null);
        $this->company->setDescription(new CompanyLocalisedText('A very long description.', null));
        $this->company->setWebsite(new CompanyLocalisedText('https://gewis.nl', null));
        $this->company->setPublished(true);
    }

    protected function setUpMockNewCompanyUser(): void
    {
        $this->newCompanyUser = new NewCompanyUser($this->company);
        $this->newCompanyUser->setCode('ynxpQ2TAjMXfvWHejcqyxJifa3LNZc3kGm6FUUBiEzSbkAFr');
        $this->newCompanyUser->setTime(new DateTime());
    }

    protected function setUpMockCompanyUser(): void
    {
        $this->companyUser = new CompanyUser($this->newCompanyUser);
        $this->companyUser->setPassword('$2y$13$qYvlgCxG331xQIJuO4l3xuEnnDk7dpAhZbTytyLkfB6Lzxj40eJpy');
    }
}
