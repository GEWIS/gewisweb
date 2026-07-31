<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\Application\Enums\NotificationType;
use App\Message\User\SecurityNotificationMessage;
use App\Security\User\Firewall;
use App\Security\User\RequestOrigin;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

/**
 * The one way to tell an account's owner that something happened to it.
 *
 * Raising the notice is best effort on purpose. Every caller has already done the thing being reported (the password
 * is changed, the session exists), so a broker that is down must not turn a completed action into an error the member
 * sees, and must never cost somebody their sign-in.
 */
final readonly class SecurityNotifier
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private RequestOrigin $requestOrigin,
        private LoggerInterface $logger,
    ) {
    }

    public function notify(
        Firewall $firewall,
        string $userIdentifier,
        NotificationType $type,
        Request $request,
    ): void {
        try {
            $this->messageBus->dispatch(new SecurityNotificationMessage(
                $firewall->value,
                $userIdentifier,
                $type,
                $this->requestOrigin->describe($request),
                new DateTimeImmutable(),
            ));
        } catch (Throwable $e) {
            $this->logger->warning(
                'Could not raise a security notice.',
                [
                    'type' => $type->value,
                    'user' => $userIdentifier,
                    'firewall' => $firewall->value,
                    'exception' => $e,
                ],
            );
        }
    }
}
