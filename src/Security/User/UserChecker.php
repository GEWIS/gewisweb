<?php

declare(strict_types=1);

namespace App\Security\User;

use App\Entity\User\User;
use Override;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class UserChecker implements UserCheckerInterface
{
    public function __construct(private TranslatorInterface $translator)
    {
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

    #[Override]
    public function checkPostAuth(
        UserInterface $user,
        ?TokenInterface $token = null,
    ): void {
    }
}
