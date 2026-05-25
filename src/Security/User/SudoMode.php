<?php

declare(strict_types=1);

namespace App\Security\User;

use Psr\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\RequestStack;

use function is_int;
use function max;

/**
 * Time-bounded "sudo mode" grant, stored on the PHP session.
 *
 * Granted by the sudo-confirmation flow after the user re-proves identity; either by providing their password or their
 * password + MFA if that is enabled for their account. Checked by {@see SudoVoter} on the 'SUDO' attribute.
 */
final class SudoMode
{
    /**
     * Session attribute key. Underscore prefix matches Symfony's `_security_*` convention.
     */
    private const string SESSION_KEY = '_sudo_granted_at';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ClockInterface $clock,
        private readonly int $ttlSeconds = 600,
    ) {
    }

    public function isActive(): bool
    {
        return $this->remainingSeconds() > 0;
    }

    public function grant(): void
    {
        $session = $this->requestStack->getSession();
        $session->set(
            self::SESSION_KEY,
            $this->clock->now()->getTimestamp(),
        );
    }

    public function revoke(): void
    {
        $session = $this->requestStack->getSession();
        $session->remove(self::SESSION_KEY);
    }

    public function remainingSeconds(): int
    {
        $request = $this->requestStack->getMainRequest();
        if (
            null === $request
            || !$request->hasPreviousSession()
        ) {
            return 0;
        }

        $session = $request->getSession();
        $grantedAt = $session->get(self::SESSION_KEY);
        if (!is_int($grantedAt)) {
            return 0;
        }

        return max(
            0,
            $grantedAt + $this->ttlSeconds - $this->clock->now()->getTimestamp(),
        );
    }
}
