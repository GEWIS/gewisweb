<?php

namespace User\Service;

use Laminas\Mvc\I18n\Translator;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Resource\GenericResource as Resource;
use Laminas\Permissions\Acl\Role\GenericRole as Role;
use User\Authentication\{
    ApiAuthenticationService,
    AuthenticationService,
};
use User\Authorization\GenericAclService;

class AclService extends GenericAclService
{
    protected Acl $acl;

    public function __construct(
        Translator $translator,
        AuthenticationService $authService,
        ApiAuthenticationService $apiAuthService,
        string $remoteAddress,
        string $tueRange,
    ) {
        parent::__construct($translator, $authService, $apiAuthService, $remoteAddress, $tueRange);
        $this->createAcl();
    }

    /**
     * @return Acl
     */
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
         * - apiuser: Automated tool given access by an admin
         * - admin: Defined administrators
         * - photo_guest: Special role for non-members but friends of GEWIS nonetheless
         */
        $this->acl->addRole(new Role('guest'));
        $this->acl->addRole(new Role('tueguest'), 'guest');
        $this->acl->addRole(new Role('user'), 'tueguest');
        $this->acl->addrole(new Role('apiuser'), 'guest');
        $this->acl->addrole(new Role('active_member'), 'user');
        $this->acl->addRole(new Role('graduate'), 'user');
        $this->acl->addrole(new Role('company_admin'), 'active_member');
        $this->acl->addRole(new Role('admin'));
        $this->acl->addRole(new Role('photo_guest'), 'guest');

        // admins (this includes board members) are allowed to do everything
        $this->acl->allow('admin');

        // configure the user ACL
        $this->acl->addResource(new Resource('apiuser'));
        $this->acl->addResource(new Resource('user'));

        $this->acl->allow('user', 'user', ['password_change']);
        $this->acl->allow('photo_guest', 'user', ['password_change']);
    }
}
