<?php

declare(strict_types=1);

namespace App\Tests\Service\Application;

use App\Entity\Application\Enums\AlertTypes;
use App\Service\Application\RealtimeNotifier;
use App\Service\Application\RealtimePayload;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

use function json_decode;

use const JSON_THROW_ON_ERROR;

final class RealtimeNotifierTest extends TestCase
{
    /** @var list<Update> */
    private array $updates = [];

    public function testSessionInvalidateTargetsThePrivateSessionTopic(): void
    {
        $this->notifier()->invalidateSession(
            'main',
            'series-abc',
            '/en/user/login?reason=session_revoked',
        );

        self::assertCount(
            1,
            $this->updates,
        );
        $update = $this->updates[0];
        self::assertSame(
            ['gewis/session/main/series-abc'],
            $update->getTopics(),
        );
        self::assertTrue($update->isPrivate());
        self::assertSame(
            [
                'type' => 'session.invalidate',
                'redirect' => '/en/user/login?reason=session_revoked',
            ],
            json_decode(
                $update->getData(),
                true,
                512,
                JSON_THROW_ON_ERROR,
            ),
        );
    }

    public function testToUserTargetsThePrivatePerUserTopic(): void
    {
        $this->notifier()->toUser(
            'company',
            '42',
            new RealtimePayload(
                AlertTypes::Info,
                [
                    'en' => 'Hello',
                    'nl' => 'Hallo',
                ],
            ),
        );

        self::assertSame(
            ['gewis/user/company/42'],
            $this->updates[0]->getTopics(),
        );
        self::assertTrue($this->updates[0]->isPrivate());
    }

    public function testToPublicIsWorldReadable(): void
    {
        $this->notifier()->toPublic(new RealtimePayload(
            AlertTypes::Warning,
            [
                'en' => 'Maintenance soon',
                'nl' => 'Onderhoud binnenkort',
            ],
        ));

        self::assertSame(
            ['gewis/public'],
            $this->updates[0]->getTopics(),
        );
        self::assertFalse($this->updates[0]->isPrivate());
    }

    private function notifier(): RealtimeNotifier
    {
        $hub = self::createStub(HubInterface::class);
        $hub->method('publish')->willReturnCallback(
            function (Update $update): string {
                $this->updates[] = $update;

                return '';
            },
        );

        return new RealtimeNotifier($hub);
    }
}
