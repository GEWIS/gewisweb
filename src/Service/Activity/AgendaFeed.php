<?php

declare(strict_types=1);

namespace App\Service\Activity;

use App\ViewModel\Activity\Calendar\AgendaEvent;
use DateTimeImmutable;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function count;
use function is_array;
use function is_string;
use function rawurlencode;
use function sprintf;
use function str_contains;
use function trim;

/**
 * The association's own agenda, for the things that only live there: an exam week, the intro week, a booking made
 * outside GEWISWEB. A day carrying one of those is not really free however empty the option calendar looks.
 *
 * Read from the cache and nowhere else. {@see \App\Command\Activity\SyncAgendaCommand} fills it; a page render never
 * waits on somebody else's server, and an agenda that is unreachable costs the calendar nothing but a missing layer.
 * Nothing is fetched on a miss either, deliberately: a cold cache would otherwise turn whoever happens to look first
 * into the one who waits.
 *
 * With no key configured the whole thing is simply off, which is what a development machine wants.
 */
final readonly class AgendaFeed
{
    private const string CACHE_KEY = 'activity.agenda-feed';

    /**
     * This is how long an unreachable agenda may go on being drawn, not how fresh the copy is kept: the refresh runs
     * every quarter of an hour, so reaching the end of this means the better part of two days of failed runs.
     */
    private const int TTL_SECONDS = 172800;

    /**
     * The stretch fetched in one go. Back a little, because a run that started last month still covers days this one,
     * and far enough ahead to cover any round the board could reasonably have opened.
     */
    private const string WINDOW_FROM = '-2 months';
    private const string WINDOW_UNTIL = '+18 months';

    public function __construct(
        #[Autowire(service: 'google_calendar.client')]
        private HttpClientInterface $googleCalendarClient,
        private CacheItemPoolInterface $cache,
        private string $googleCalendarId,
        private string $googleApiKey,
    ) {
    }

    /**
     * Whether an agenda is configured at all. A machine without a key shows the calendar without this layer rather
     * than complaining about it.
     */
    public function isConfigured(): bool
    {
        return '' !== trim($this->googleCalendarId)
            && 'unknown' !== $this->googleCalendarId
            && '' !== trim($this->googleApiKey)
            && 'unknown' !== $this->googleApiKey;
    }

    /**
     * What is on the agenda in the given stretch of days. Never fetches.
     *
     * @return AgendaEvent[]
     */
    public function eventsBetween(
        DateTimeImmutable $from,
        DateTimeImmutable $until,
    ): array {
        $item = $this->cache->getItem(self::CACHE_KEY);

        if (!$item->isHit()) {
            return [];
        }

        $cached = $item->get();

        if (!is_array($cached)) {
            return [];
        }

        $events = [];
        foreach ($cached as $row) {
            $event = $this->hydrate($row);

            if (null === $event) {
                continue;
            }

            if (
                $event->startsOn > $until
                || $event->endsOn < $from
            ) {
                continue;
            }

            $events[] = $event;
        }

        return $events;
    }

    /**
     * Fetch the agenda and keep it. Returns how many days' worth came back, or null when the fetch failed, so the
     * caller can say which of the two happened.
     *
     * A failed fetch leaves whatever was kept alone: a stale agenda is a great deal more use than none.
     */
    public function refresh(): ?int
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $rows = $this->fetch();

        if (null === $rows) {
            return null;
        }

        $item = $this->cache->getItem(self::CACHE_KEY);
        $item->set($rows);
        $item->expiresAfter(self::TTL_SECONDS);
        $this->cache->save($item);

        return count($rows);
    }

    /**
     * @return list<array{title: string, startsOn: string, endsOn: string}>|null
     */
    private function fetch(): ?array
    {
        try {
            $body = $this->googleCalendarClient->request(
                'GET',
                sprintf(
                    'calendars/%s/events',
                    rawurlencode($this->calendarId()),
                ),
                [
                    'query' => [
                        'timeMin' => new DateTimeImmutable(self::WINDOW_FROM)->format('c'),
                        'timeMax' => new DateTimeImmutable(self::WINDOW_UNTIL)->format('c'),
                        // Repeating events are expanded into the individual days they fall on, which is the only
                        // shape a calendar grid can draw.
                        'singleEvents' => 'true',
                        'orderBy' => 'startTime',
                        'maxResults' => 2500,
                    ],
                ],
            )->toArray();
        } catch (ExceptionInterface) {
            return null;
        }

        $items = $body['items'] ?? null;

        if (!is_array($items)) {
            return null;
        }

        $rows = [];
        foreach ($items as $item) {
            $row = $this->rowFor($item);

            if (null === $row) {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return array{title: string, startsOn: string, endsOn: string}|null
     */
    private function rowFor(mixed $item): ?array
    {
        if (!is_array($item)) {
            return null;
        }

        $title = $item['summary'] ?? null;
        $start = $this->dayOf($item['start'] ?? null);
        $end = $this->dayOf($item['end'] ?? null);

        if (
            !is_string($title)
            || null === $start
            || null === $end
        ) {
            return null;
        }

        // An all-day event's end is the day *after* it finishes, so a Friday-only event comes back ending on the
        // Saturday. Drawn as it arrives it would take a day nobody is actually using, which is a mistake this
        // calendar has made before.
        if (null !== ($item['end']['date'] ?? null)) {
            $end = $end->modify('-1 day');
        }

        if ($end < $start) {
            $end = $start;
        }

        return [
            'title' => trim($title),
            'startsOn' => $start->format('Y-m-d'),
            'endsOn' => $end->format('Y-m-d'),
        ];
    }

    /**
     * The day of a Google start/end, which is either a bare date for an all-day event or a full timestamp.
     */
    private function dayOf(mixed $moment): ?DateTimeImmutable
    {
        if (!is_array($moment)) {
            return null;
        }

        $value = $moment['date'] ?? $moment['dateTime'] ?? null;

        if (!is_string($value)) {
            return null;
        }

        $parsed = DateTimeImmutable::createFromFormat(
            DateTimeImmutable::ATOM,
            $value,
        );

        if (false === $parsed) {
            $parsed = DateTimeImmutable::createFromFormat(
                'Y-m-d',
                $value,
            );
        }

        return false === $parsed
            ? null
            : $parsed->setTime(
                0,
                0,
            );
    }

    /**
     * The production value has always been the bare key, with the shared-calendar suffix added where it was used, so
     * that keeps working; a full address is taken as it stands.
     */
    private function calendarId(): string
    {
        $id = trim($this->googleCalendarId);

        return str_contains(
            $id,
            '@',
        )
            ? $id
            : $id . '@group.calendar.google.com';
    }

    private function hydrate(mixed $row): ?AgendaEvent
    {
        if (!is_array($row)) {
            return null;
        }

        $title = $row['title'] ?? null;
        $startsOn = $row['startsOn'] ?? null;
        $endsOn = $row['endsOn'] ?? null;

        if (
            !is_string($title)
            || !is_string($startsOn)
            || !is_string($endsOn)
        ) {
            return null;
        }

        $start = DateTimeImmutable::createFromFormat(
            'Y-m-d',
            $startsOn,
        );
        $end = DateTimeImmutable::createFromFormat(
            'Y-m-d',
            $endsOn,
        );

        if (
            false === $start
            || false === $end
        ) {
            return null;
        }

        return new AgendaEvent(
            $title,
            $start->setTime(
                0,
                0,
            ),
            $end->setTime(
                0,
                0,
            ),
        );
    }
}
