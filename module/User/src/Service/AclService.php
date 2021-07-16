<?php

namespace User\Service;

use DateTime;
use Decision\Model\BoardMember;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Resource\GenericResource as Resource;
use Laminas\Permissions\Acl\Role\GenericRole as Role;
use User\Authentication\ApiAuthenticationService;
use User\Authentication\AuthenticationService;
use User\Authorization\GenericAclService;
use User\Model\User;

class AclService extends GenericAclService
{
    protected Acl $acl;

    public function __construct(
        TranslatorInterface $translator,
        AuthenticationService $authService,
        ApiAuthenticationService $apiAuthService,
        string $remoteAddress,
        string $tueRange
    ) {
        parent::__construct($translator, $authService, $apiAuthService, $remoteAddress, $tueRange);
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
        $this->acl->addRole(new Role('board'));
        $this->acl->addRole(new Role('admin'));
        $this->acl->addRole(new Role('photo_guest'), 'guest');

        $user = $this->getIdentity();

        // add user to registry
        if ($user instanceof User) {
            $roles = $user->getRoleNames();

            // If the user has no roles, add the 'user' role by default.
            if (empty($roles)) {
                $roles = ['user'];
            }

            if (count($user->getMember()->getCurrentOrganInstallations()) > 0) {
                $roles[] = 'active_member';
            }

            // Add the `board` role to `User`s who are part of the current board.
            foreach ($user->getMember()->getBoardInstallations() as $boardInstall) {
                if ($this->isCurrentBoard($boardInstall)) {
                    $roles[] = 'board';
                }
            }

            // Laminas processes inherited roles LIFO (Last-In-First-Out), hence we need to ensure that more privileged
            // roles are at the end of the `$roles` array. To do this we intersect a predefined order array
            // (`$roleOrder`) with the `$roles` array to obtain a correctly ordered `$roles` array.
            $roleOrder = [
                'photo_guest',
                'user',
                'active_member',
                'company_admin',
                'board',
                'admin',
            ];
            $roles = array_intersect($roleOrder, $roles);

            $this->acl->addRole($user, $roles);
        }

        // Admins are allowed to do everything.
        $this->acl->allow('admin');

        // Board members are practically admins.
        $this->acl->allow('board');

        // Configure the user ACL.
        $this->acl->addResource(new Resource('apiuser'));
        $this->acl->addResource(new Resource('user'));

        $this->acl->allow('user', 'user', ['password_change']);
        $this->acl->allow('photo_guest', 'user', ['password_change']);
        $this->acl->allow('tueguest', 'user', 'pin_login');

        // sosusers can't do anything
        $this->acl->deny('sosuser');
    }

    /**
     * Check if this is a current board member.
     *
     * @param BoardMember $boardMember
     *
     * @return bool
     */
    private function isCurrentBoard(BoardMember $boardMember): bool
    {
        $now = new DateTime();

        return $boardMember->getInstallDate() <= $now &&
            (null === $boardMember->getDischargeDate() || $boardMember->getDischargeDate() >= $now);
    }
}
