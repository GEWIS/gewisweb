<?php

declare(strict_types=1);

namespace App\MessageHandler\User;

use App\Message\User\RevokeSessionsRealtimeMessage;
use App\Security\User\Firewall;
use App\Service\Application\RealtimeNotifier;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsMessageHandler]
class RevokeSessionsRealtimeHandler
{
    public function __construct(
        private readonly RealtimeNotifier $notifier,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(RevokeSessionsRealtimeMessage $message): void
    {
        $loginRoute = Firewall::tryFrom($message->getFirewallName())?->loginRoute();
        if (null === $loginRoute) {
            return;
        }

        // The worker has no request locale; English matches the house convention for system-generated content and the
        // login page carries a language switcher.
        $redirect = $this->urlGenerator->generate(
            $loginRoute,
            [
                '_locale' => 'en',
                'reason' => 'session_revoked',
            ],
        );

        foreach ($message->getSeries() as $series) {
            $this->notifier->invalidateSession(
                $message->getFirewallName(),
                $series,
                $redirect,
            );
        }
    }
}
