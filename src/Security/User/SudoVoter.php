<?php

declare(strict_types=1);

namespace App\Security\User;

use Override;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Grants the `SUDO` attribute when the user holds a live sudo-mode session grant.
 *
 * Layered on top of {@see Symfony\Component\Security\Http\Authorization\AuthenticatedVoter}'s
 * `IS_AUTHENTICATED_REMEMBERED` check. We must never grant SUDO to a fully-anonymous token.
 *
 * Used via {@code #[IsGranted('SUDO')]} on destructive controller actions.
 *
 * @extends Voter<string, mixed>
 */
final class SudoVoter extends Voter
{
    public const string ATTRIBUTE = 'SUDO';

    public function __construct(
        private readonly SudoMode $sudoMode,
    ) {
    }

    #[Override]
    protected function supports(
        string $attribute,
        mixed $subject,
    ): bool {
        return self::ATTRIBUTE === $attribute;
    }

    #[Override]
    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        if ($token instanceof NullToken) {
            return false;
        }

        return $this->sudoMode->isActive();
    }
}
