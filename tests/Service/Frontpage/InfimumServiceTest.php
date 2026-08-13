<?php

declare(strict_types=1);

namespace App\Tests\Service\Frontpage;

use App\Service\Frontpage\InfimumService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

use function json_encode;
use function strval;

/**
 * The infimum comes from somebody else's server, so what matters is what happens when that server is unhelpful: a page
 * load must never wait on it twice in a row, and a failed fetch must leave whoever is reading with what they had
 * rather than with an apology.
 */
final class InfimumServiceTest extends TestCase
{
    public function testTheInfimumIsFetchedOnceAndThenKept(): void
    {
        $responses = [
            $this->infimum('GEWISWEB-ng wordt steeds beter'),
            $this->infimum('Something else entirely'),
        ];
        $client = new MockHttpClient($responses);
        $service = new InfimumService(
            $client,
            new ArrayAdapter(),
        );

        self::assertSame(
            'GEWISWEB-ng wordt steeds beter',
            $service->getInfimum(),
        );
        self::assertSame(
            'GEWISWEB-ng wordt steeds beter',
            $service->getInfimum(),
        );
        self::assertSame(
            1,
            $client->getRequestsCount(),
        );
    }

    public function testRefreshingAsksAgainWhateverIsKept(): void
    {
        $client = new MockHttpClient([
            $this->infimum('First'),
            $this->infimum('Second'),
        ]);
        $service = new InfimumService(
            $client,
            new ArrayAdapter(),
        );

        self::assertSame(
            'First',
            $service->getInfimum(),
        );
        self::assertSame(
            'Second',
            $service->refresh(),
        );
        // The new one is what is kept, so the next read does not go back to the old one.
        self::assertSame(
            'Second',
            $service->getInfimum(),
        );
        self::assertSame(
            2,
            $client->getRequestsCount(),
        );
    }

    /**
     * A server that cannot answer is asked once and then left alone for a while: the footer is on every page, so
     * asking again on each of them would make somebody else's outage this website's.
     *
     * @param callable():MockResponse $response
     */
    #[DataProvider('unhelpfulResponses')]
    public function testAnUnhelpfulServerIsNotAskedAgainOnTheNextRead(callable $response): void
    {
        $cache = new ArrayAdapter();
        $client = new MockHttpClient([
            $response(),
            $response(),
        ]);
        $service = new InfimumService(
            $client,
            $cache,
        );

        self::assertNull($service->getInfimum());
        self::assertNull($service->getInfimum());
        self::assertSame(
            1,
            $client->getRequestsCount(),
        );
    }

    /**
     * @return iterable<string, array{callable():MockResponse}>
     */
    public static function unhelpfulResponses(): iterable
    {
        yield 'refused' => [
            static fn (): MockResponse => new MockResponse(
                '',
                ['http_code' => 401],
            ),
        ];

        yield 'broken' => [
            static fn (): MockResponse => new MockResponse(
                '',
                ['http_code' => 500],
            ),
        ];

        yield 'not json' => [static fn (): MockResponse => new MockResponse('not json at all')];

        yield 'no infimum in it' => [
            static fn (): MockResponse => new MockResponse(strval(json_encode(['status' => 'ok']))),
        ];

        yield 'an empty infimum' => [
            static fn (): MockResponse => new MockResponse(strval(json_encode(['content' => '  ']))),
        ];
    }

    /**
     * A failed refresh leaves what was kept alone, so a hiccup in the rotation does not blank the panel.
     */
    public function testAFailedRefreshKeepsWhatWasThere(): void
    {
        $cache = new ArrayAdapter();
        $service = new InfimumService(
            new MockHttpClient([
                $this->infimum('Still good'),
                new MockResponse(
                    '',
                    ['http_code' => 503],
                ),
            ]),
            $cache,
        );

        self::assertSame(
            'Still good',
            $service->getInfimum(),
        );
        self::assertNull($service->refresh());
        self::assertSame(
            'Still good',
            $service->getInfimum(),
        );
    }

    private function infimum(string $content): MockResponse
    {
        return new MockResponse(
            strval(json_encode(['content' => $content])),
            ['response_headers' => ['content-type' => 'application/json']],
        );
    }
}
