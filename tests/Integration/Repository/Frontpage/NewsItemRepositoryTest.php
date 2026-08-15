<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository\Frontpage;

use App\Entity\Frontpage\NewsItem;
use App\Repository\Frontpage\NewsItemRepository;
use App\Tests\Integration\DatabaseTestCase;

use function array_map;
use function array_slice;
use function count;
use function rsort;

/**
 * The order the feed comes out in is the whole of what the repository decides, and it is the one thing a reader
 * notices when it is wrong: what the board pinned first, then everything else newest first.
 */
final class NewsItemRepositoryTest extends DatabaseTestCase
{
    public function testWhatTheBoardPinnedComesFirst(): void
    {
        $feed = $this->repository()->findFeed(limit: 50);
        self::assertGreaterThan(
            1,
            count($feed),
        );

        $pinned = 0;
        foreach ($feed as $item) {
            if (!$item->getPinned()) {
                break;
            }

            ++$pinned;
        }

        self::assertGreaterThan(
            0,
            $pinned,
            'The seed is expected to contain a pinned news item.',
        );

        foreach (
            array_slice(
                $feed,
                $pinned,
            ) as $item
        ) {
            self::assertFalse($item->getPinned());
        }
    }

    public function testEverythingElseIsNewestFirst(): void
    {
        $unpinned = [];
        foreach ($this->repository()->findFeed(limit: 50) as $item) {
            if ($item->getPinned()) {
                continue;
            }

            $unpinned[] = $item;
        }

        $dates = array_map(
            static fn (NewsItem $item): int => $item->getDate()->getTimestamp(),
            $unpinned,
        );

        $sorted = $dates;
        rsort($sorted);

        self::assertSame(
            $sorted,
            $dates,
        );
    }

    public function testTheArchivePagesThroughTheSameOrder(): void
    {
        $feed = $this->repository()->findFeed(limit: 3);
        $paginator = $this->repository()->getPaginatorAdapter(
            1,
            3,
        );

        self::assertSame(
            array_map(
                static fn (NewsItem $item): ?int => $item->getId(),
                $feed,
            ),
            array_map(
                static fn (NewsItem $item): ?int => $item->getId(),
                [...$paginator],
            ),
        );
    }

    private function repository(): NewsItemRepository
    {
        return self::getContainer()->get(NewsItemRepository::class);
    }
}
