<?php

declare(strict_types=1);

namespace App\Tests\Command\Frontpage;

use App\Command\Frontpage\RotateInfimumCommand;
use App\Service\Application\RealtimeNotifier;
use App\Service\Frontpage\InfimumService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

use function json_decode;
use function json_encode;
use function strval;

use const JSON_THROW_ON_ERROR;

/**
 * A rotation that fetched nothing must publish nothing: whoever is reading keeps the infimum they already had, which
 * is a better answer than blanking the panel because somebody else's server had a bad minute.
 */
final class RotateInfimumCommandTest extends TestCase
{
    /** @var list<Update> */
    private array $updates = [];

    public function testAFreshInfimumIsPushedToTheMembers(): void
    {
        $tester = $this->tester(new MockResponse(
            strval(json_encode(['content' => 'GEWISWEB-ng wordt steeds beter'])),
            ['response_headers' => ['content-type' => 'application/json']],
        ));

        $tester->execute([]);

        self::assertCount(
            1,
            $this->updates,
        );
        $update = $this->updates[0];
        self::assertSame(
            ['gewis/members'],
            $update->getTopics(),
        );
        self::assertTrue($update->isPrivate());
        self::assertSame(
            [
                'type' => 'infimum.rotate',
                'infimum' => 'GEWISWEB-ng wordt steeds beter',
            ],
            json_decode(
                $update->getData(),
                true,
                512,
                JSON_THROW_ON_ERROR,
            ),
        );
    }

    public function testNothingIsPublishedWhenNothingCouldBeFetched(): void
    {
        $tester = $this->tester(new MockResponse(
            '',
            ['http_code' => 503],
        ));

        $tester->execute([]);

        self::assertSame(
            [],
            $this->updates,
        );
        self::assertStringContainsString(
            'nothing was published',
            $tester->getDisplay(),
        );
    }

    private function tester(MockResponse $response): CommandTester
    {
        $hub = self::createStub(HubInterface::class);
        $hub->method('publish')->willReturnCallback(
            function (Update $update): string {
                $this->updates[] = $update;

                return '';
            },
        );

        return new CommandTester(new RotateInfimumCommand(
            new InfimumService(
                new MockHttpClient([$response]),
                new ArrayAdapter(),
            ),
            new RealtimeNotifier($hub),
            new NullLogger(),
        ));
    }
}
