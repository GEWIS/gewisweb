<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\Enums\RealtimeEventType;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

use function json_encode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

/**
 * The single place server-to-client Mercure pushes go through. Topic strings match the subscribe topics minted in the
 * base layout. Session and user topics are per-principal so they are published privately (delivered only to a JWT that
 * lists the exact topic); the public topic is world-readable.
 */
final class RealtimeNotifier
{
    public function __construct(
        private readonly HubInterface $hub,
    ) {
    }

    public function invalidateSession(
        string $firewallName,
        string $series,
        string $redirect,
    ): void {
        $this->publish(
            sprintf(
                'gewis/session/%s/%s',
                $firewallName,
                $series,
            ),
            [
                'type' => RealtimeEventType::SessionInvalidate->value,
                'redirect' => $redirect,
            ],
            true,
        );
    }

    public function toUser(
        string $firewallName,
        string $userIdentifier,
        RealtimePayload $payload,
    ): void {
        $this->publish(
            sprintf(
                'gewis/user/%s/%s',
                $firewallName,
                $userIdentifier,
            ),
            $payload->toArray(),
            true,
        );
    }

    public function toPublic(RealtimePayload $payload): void
    {
        $this->publish(
            'gewis/public',
            $payload->toArray(),
            false,
        );
    }

    /**
     * Tells every connected client to reload, e.g. right before maintenance so they land on the maintenance page.
     */
    public function reloadPublic(): void
    {
        $this->publish(
            'gewis/public',
            ['type' => RealtimeEventType::ForceReload->value],
            false,
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function publish(
        string $topic,
        array $data,
        bool $private,
    ): void {
        $this->hub->publish(new Update(
            $topic,
            json_encode(
                $data,
                JSON_THROW_ON_ERROR,
            ),
            private: $private,
        ));
    }
}
