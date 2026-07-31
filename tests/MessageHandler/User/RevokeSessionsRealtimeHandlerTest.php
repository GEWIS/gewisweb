<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler\User;

use App\Message\User\RevokeSessionsRealtimeMessage;
use App\MessageHandler\User\RevokeSessionsRealtimeHandler;
use App\Service\Application\RealtimeNotifier;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class RevokeSessionsRealtimeHandlerTest extends TestCase
{
    public function testItPublishesOneSessionInvalidatePerSeriesWithTheFirewallLoginRedirect(): void
    {
        $updates = [];
        $hub = self::createStub(HubInterface::class);
        $hub->method('publish')->willReturnCallback(
            static function (Update $update) use (&$updates): string {
                $updates[] = $update;

                return '';
            },
        );

        $urlGenerator = self::createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with(
                'user_login',
                [
                    '_locale' => 'en',
                    'reason' => 'session_revoked',
                ],
            )
            ->willReturn('/en/user/login?reason=session_revoked');

        $handler = new RevokeSessionsRealtimeHandler(
            new RealtimeNotifier($hub),
            $urlGenerator,
        );
        $handler->__invoke(new RevokeSessionsRealtimeMessage('main', ['aaa', 'bbb']));

        self::assertCount(
            2,
            $updates,
        );
        self::assertSame(
            ['gewis/session/main/aaa'],
            $updates[0]->getTopics(),
        );
        self::assertSame(
            ['gewis/session/main/bbb'],
            $updates[1]->getTopics(),
        );
        self::assertTrue($updates[0]->isPrivate());
    }

    public function testAnUnknownFirewallPublishesNothing(): void
    {
        $hub = self::createMock(HubInterface::class);
        $hub->expects(self::never())->method('publish');

        $urlGenerator = self::createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::never())->method('generate');

        $handler = new RevokeSessionsRealtimeHandler(
            new RealtimeNotifier($hub),
            $urlGenerator,
        );
        $handler->__invoke(new RevokeSessionsRealtimeMessage('nonexistent', ['aaa']));
    }
}
