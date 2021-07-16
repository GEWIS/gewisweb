<?php

namespace User\Service;

use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Resource\GenericResource as Resource;
use Laminas\Permissions\Acl\Role\GenericRole as Role;
use User\Authentication\AuthenticationService;
use User\Authorization\GenericAclService;
use User\Model\User;
use User\Permissions\Assertion\IsBoardMember;

class AclService extends GenericAclService
{
    protected Acl $acl;

    public function __construct(
        TranslatorInterface $translator,
        AuthenticationService $authService,
        string $remoteAddress,
        string $tueRange
    ) {
        parent::__construct($translator, $authService, $remoteAddress, $tueRange);
        $this->createAcl();
    }

    protected function getAcl(): Acl
    {
        return $this->acl;
    }

    protected function createAcl()
    {
        // initialize the ACL
        $this->acl = new Acl();

        /*
         * Define all basic roles.
         *
         * - guest: everyone gets at least this access level
         * - tueguest: guest from the TU/e
         * - user: GEWIS-member
         * - apiuser: Automated tool given access by an admin
         * - admin: Defined administrators
         * - photo_guest: Special role for non-members but friends of GEWIS nonetheless
         */

        $this->acl->addRole(new Role('guest'));
        $this->acl->addRole(new Role('tueguest'), 'guest');
        $this->acl->addRole(new Role('user'), 'tueguest');
        $this->acl->addrole(new Role('apiuser'), 'guest');
        $this->acl->addrole(new Role('sosuser'), 'apiuser');
        $this->acl->addrole(new Role('active_member'), 'user');
        $this->acl->addrole(new Role('company_admin'), 'active_member');
        $this->acl->addRole(new Role('admin'));
        $this->acl->addRole(new Role('photo_guest'), 'guest');

        $user = $this->getIdentity();

        // add user to registry
        if ($user instanceof User) {
            $roles = $user->getRoleNames();
            // if the user has no roles, add the 'user' role by default
            if (empty($roles)) {
                $roles = ['user'];
            }

            if (count($user->getMember()->getCurrentOrganInstallations()) > 0) {
                $roles[] = 'active_member';
            }

            $this->acl->addRole($user, $roles);
        }

        // admins are allowed to do everything
        $this->acl->allow('admin');

        // board members also are admins
        $this->acl->allow('user', null, null, new IsBoardMember());

        // configure the user ACL
        $this->acl->addResource(new Resource('apiuser'));
        $this->acl->addResource(new Resource('user'));

        $this->acl->allow('user', 'user', ['password_change']);
        $this->acl->allow('photo_guest', 'user', ['password_change']);
        $this->acl->allow('tueguest', 'user', 'pin_login');

        // sosusers can't do anything
        $this->acl->deny('sosuser');
    }
}
