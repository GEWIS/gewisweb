<?php

declare(strict_types=1);

namespace User\Service;

use Laminas\Mvc\I18n\Translator;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Resource\GenericResource as Resource;
use Laminas\Permissions\Acl\Role\GenericRole as Role;
use User\Authentication\Adapter\CompanyUserAdapter;
use User\Authentication\Adapter\UserAdapter;
use User\Authentication\ApiAuthenticationService;
use User\Authentication\AuthenticationService as CompanyUserAuthenticationService;
use User\Authentication\AuthenticationService as UserAuthenticationService;
use User\Authentication\Storage\CompanyUserSession;
use User\Authentication\Storage\UserSession;
use User\Authorization\GenericAclService;

class AclService extends GenericAclService
{
    protected Acl $acl;

    /**
     * @param string[] $tueRanges
     * @psalm-param UserAuthenticationService<UserSession, UserAdapter> $userAuthService
     * @psalm-param CompanyUserAuthenticationService<CompanyUserSession, CompanyUserAdapter> $companyUserAuthService
     */
    public function __construct(
        Translator $translator,
        UserAuthenticationService $userAuthService,
        CompanyUserAuthenticationService $companyUserAuthService,
        ApiAuthenticationService $apiUserAuthService,
        array $tueRanges,
        string $remoteAddress,
    ) {
        parent::__construct(
            $translator,
            $userAuthService,
            $companyUserAuthService,
            $apiUserAuthService,
            $tueRanges,
            $remoteAddress,
        );

        $this->createAcl();
    }

    protected function getAcl(): Acl
    {
        return $this->acl;
    }

    protected function createAcl(): void
    {
        // initialize the ACL
        $this->acl = new Acl();

        /*
         * Define all basic roles.
         *
         * - guest: everyone gets at least this access level
         * - tueguest: guest from the TU/e
         * - user: GEWIS-member
         * - active_member: a GEWIS-member who is part on an organ
         * - graduate: an old GEWIS-member, has limited privileges
         * - company: a company which uses the career section of the website
         * - apiuser: Automated tool given access by an admin
         * - admin: Defined administrators
         */
        $this->acl->addRole(new Role('guest'));
        $this->acl->addRole(new Role('tueguest'), 'guest');
        $this->acl->addRole(new Role('user'), 'tueguest');
        $this->acl->addRole(new Role('company'), 'guest');
        $this->acl->addrole(new Role('apiuser'), 'guest');
        $this->acl->addrole(new Role('active_member'), 'user');
        $this->acl->addRole(new Role('graduate'), 'user');
        $this->acl->addrole(new Role('company_admin'), 'active_member');
        $this->acl->addRole(new Role('admin'));

        // admins (this includes board members) are allowed to do everything
        $this->acl->allow('admin');

        // configure the user ACL
        $this->acl->addResource(new Resource('apiuser'));
        $this->acl->addResource(new Resource('user'));

        $this->acl->allow('user', 'user', ['password_change']);
        $this->acl->allow('company', 'user', ['password_change']);
    }
}
