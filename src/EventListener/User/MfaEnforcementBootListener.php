<?php

declare(strict_types=1);

namespace App\EventListener\User;

use App\Security\User\MfaEnforcementSwitch;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Pushes the bound `app.mfa.enforcement_enabled` value into {@see MfaEnforcementSwitch} at the very start of every
 * request, so that subsequent {@see \App\Entity\User\User::getRoles()} calls (issued by Symfony's authorization stack)
 * see the correct value before any role-based decision is made.
 *
 * Runs at priority 4096, well ahead of the firewall (priority 8) and the role voters that follow it.
 */
#[AsEventListener(
    event: RequestEvent::class,
    priority: 4096,
)]
final readonly class MfaEnforcementBootListener
{
    public function __construct(private bool $mfaEnforcementEnabled)
    {
    }

    public function __invoke(RequestEvent $event): void
    {
        MfaEnforcementSwitch::setEnabled($this->mfaEnforcementEnabled);
    }
}
