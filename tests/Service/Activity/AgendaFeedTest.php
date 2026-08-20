<?php

declare(strict_types=1);

namespace App\Tests\Service\Activity;

use App\Service\Activity\AgendaFeed;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

use function json_encode;

/**
 * Reading the association's own agenda.
 *
 * Two things this has to get right. A page render must never wait on somebody else's server, so a miss answers with
 * nothing rather than reaching out. And an all-day event comes back ending on the day *after* it finishes, which the
 * option calendar has drawn wrong before: a Friday-only event would take the Saturday off everybody.
 *
 * No database in sight, so a plain unit test with a mocked client and a cache per case.
 */
final class AgendaFeedTest extends TestCase
{
    public function testAColdCacheAnswersWithNothingRatherThanFetching(): void
    {
        $client = new MockHttpClient([]);
        $feed = $this->feed($client);

        self::assertSame(
            [],
            $feed->eventsBetween(
                new DateTimeImmutable('2026-01-01'),
                new DateTimeImmutable('2026-12-31'),
            ),
        );
        self::assertSame(
            0,
            $client->getRequestsCount(),
            'A render must never reach out to somebody else\'s server.',
        );
    }

    public function testAnAllDayEventDoesNotSwallowTheDayAfterIt(): void
    {
        $feed = $this->feed($this->respondingWith([
            [
                'summary' => 'Tentamenweek',
                'start' => ['date' => '2026-11-09'],
                'end' => ['date' => '2026-11-14'],
            ],
            [
                'summary' => 'Borrel',
                'start' => ['dateTime' => '2026-11-19T16:30:00+01:00'],
                'end' => ['dateTime' => '2026-11-19T20:00:00+01:00'],
            ],
        ]));

        self::assertSame(
            2,
            $feed->refresh(),
        );

        $events = $feed->eventsBetween(
            new DateTimeImmutable('2026-11-01'),
            new DateTimeImmutable('2026-11-30'),
        );

        self::assertCount(
            2,
            $events,
        );

        // Google gives the day after the last one, so this has to come back ending on the 13th.
        self::assertSame(
            '2026-11-09',
            $events[0]->startsOn->format('Y-m-d'),
        );
        self::assertSame(
            '2026-11-13',
            $events[0]->endsOn->format('Y-m-d'),
        );

        // An event with a clock time is one day, and its end is not shifted.
        self::assertSame(
            '2026-11-19',
            $events[1]->startsOn->format('Y-m-d'),
        );
        self::assertSame(
            '2026-11-19',
            $events[1]->endsOn->format('Y-m-d'),
        );
    }

    public function testOnlyWhatTouchesTheStretchComesBack(): void
    {
        $feed = $this->feed($this->respondingWith([
            [
                'summary' => 'Ver weg',
                'start' => ['date' => '2027-06-01'],
                'end' => ['date' => '2027-06-02'],
            ],
        ]));
        $feed->refresh();

        self::assertSame(
            [],
            $feed->eventsBetween(
                new DateTimeImmutable('2026-11-01'),
                new DateTimeImmutable('2026-11-30'),
            ),
        );
    }

    public function testAFailedFetchLeavesWhatWasKeptAlone(): void
    {
        $cache = $this->cache();

        $good = $this->feed(
            $this->respondingWith([
                [
                    'summary' => 'Introweek',
                    'start' => ['date' => '2026-11-02'],
                    'end' => ['date' => '2026-11-07'],
                ],
            ]),
            $cache,
        );
        $good->refresh();

        $broken = $this->feed(
            new MockHttpClient(new MockResponse(
                '',
                ['http_code' => 503],
            )),
            $cache,
        );

        self::assertNull($broken->refresh());
        self::assertCount(
            1,
            $broken->eventsBetween(
                new DateTimeImmutable('2026-11-01'),
                new DateTimeImmutable('2026-11-30'),
            ),
            'A stale agenda is a great deal more use than none.',
        );
    }

    public function testNothingHappensWithoutAKey(): void
    {
        $client = new MockHttpClient([]);
        $feed = new AgendaFeed(
            $client,
            $this->cache(),
            'unknown',
            'unknown',
        );

        self::assertFalse($feed->isConfigured());
        self::assertNull($feed->refresh());
        self::assertSame(
            0,
            $client->getRequestsCount(),
        );
    }

    /**
     * @param array<array-key, array<string, mixed>> $items
     */
    private function respondingWith(array $items): MockHttpClient
    {
        return new MockHttpClient(new MockResponse(
            (string) json_encode(['items' => $items]),
            ['response_headers' => ['content-type' => 'application/json']],
        ));
    }

    private function feed(
        MockHttpClient $client,
        ?CacheItemPoolInterface $cache = null,
    ): AgendaFeed {
        return new AgendaFeed(
            $client,
            $cache ?? $this->cache(),
            'gewis-agenda',
            'a-key',
        );
    }

    /**
     * A cache of its own per test: the real pool outlives a test, and one test refreshing the agenda would decide
     * what the next one sees.
     */
    private function cache(): CacheItemPoolInterface
    {
        return new ArrayAdapter();
    }
}
