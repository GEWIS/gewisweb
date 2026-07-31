<?php

declare(strict_types=1);

namespace App\Security\User;

use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Service\Application\MaintenanceStatusProvider;
use Override;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function in_array;

readonly class UserChecker implements UserCheckerInterface
{
    public function __construct(
        private TranslatorInterface $translator,
        private MaintenanceStatusProvider $maintenanceStatus,
        private RoleHierarchyInterface $roleHierarchy,
    ) {
    }

    #[Override]
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (
            $user->getMember()->getDeleted()
            || $user->getMember()->getHidden()
            || $user->getMember()->isExpired()
            || null === $user->getMember()->getEmail()
        ) {
            // Blanket denial for login if state of membership/graduate status does not allow this.
            throw new CustomUserMessageAccountStatusException(
                $this->translator->trans(
                    'You cannot sign in to this account at this moment. Contact the board for more information.',
                ),
            );
        }
    }

    /**
     * While maintenance is in effect, only admins may sign in. This runs during authentication, ahead of the
     * {@see \App\EventListener\Application\MaintenanceListener}, which the firewall would otherwise bypass for a login.
     */
    #[Override]
    public function checkPostAuth(
        UserInterface $user,
        ?TokenInterface $token = null,
    ): void {
        if (null === $this->maintenanceStatus->activeWindow()) {
            return;
        }

        if (
            in_array(
                UserRoles::Admin->value,
                $this->roleHierarchy->getReachableRoleNames($user->getRoles()),
                true,
            )
        ) {
            return;
        }

        throw new CustomUserMessageAccountStatusException(
            $this->translator->trans('The website is undergoing maintenance. You cannot sign in right now.'),
        );
    }
}
