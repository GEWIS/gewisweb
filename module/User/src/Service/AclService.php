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
use User\Model\Enums\UserRoles;

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
         * - board: Members of the board, they have almost all privileges
         * - admin: Defined administrators
         */
        $this->acl->addRole(new Role(UserRoles::Guest->value));
        $this->acl->addRole(new Role(UserRoles::TueGuest->value), UserRoles::Guest->value);
        $this->acl->addRole(new Role(UserRoles::User->value), UserRoles::TueGuest->value);
        $this->acl->addRole(new Role(UserRoles::Company->value), UserRoles::Guest->value);
        $this->acl->addrole(new Role(UserRoles::ApiUser->value), UserRoles::Guest->value);
        $this->acl->addrole(new Role(UserRoles::ActiveMember->value), UserRoles::User->value);
        $this->acl->addRole(new Role(UserRoles::Graduate->value), UserRoles::User->value);
        $this->acl->addrole(new Role(UserRoles::CompanyAdmin->value), UserRoles::ActiveMember->value);
        $this->acl->addRole(new Role(UserRoles::Admin->value));
        $this->acl->addRole(new Role(UserRoles::Board->value), UserRoles::Admin->value);

        // admins (this includes board members) are allowed to do everything (board not actually)
        $this->acl->allow(UserRoles::Admin->value);

        // configure the user ACL
        $this->acl->addResource(new Resource('apiuser'));
        $this->acl->addResource(new Resource('user'));
        // Define administration part of this module, however, sub-permissions must be manually configured.
        $this->acl->addResource(new Resource('user_admin'));

        // Do not allow the board to touch user administration / API tokens
        $this->acl->deny(UserRoles::Board->value, 'user_admin');
        $this->acl->deny(UserRoles::Board->value, 'apiuser');

        // Do not allow the board to see activation status
        $this->acl->deny(UserRoles::Board->value, 'user', ['view_status']);

        $this->acl->allow(UserRoles::User->value, 'user', ['password_change']);
        $this->acl->allow(UserRoles::Company->value, 'user', ['password_change']);
    }
}
