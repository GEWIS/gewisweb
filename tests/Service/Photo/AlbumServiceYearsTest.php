<?php

declare(strict_types=1);

namespace App\Tests\Service\Photo;

use App\Repository\Photo\AlbumRepository;
use App\Repository\Photo\PhotoRepository;
use App\Service\Photo\AlbumService;
use DateTime;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * The overview's year switcher must list only the association years that actually hold a published root album: a year
 * with none (a gap between two dated albums) never appears, and a year holding several albums appears once.
 */
final class AlbumServiceYearsTest extends TestCase
{
    public function testYearSwitcherListsOnlyYearsThatHaveAlbumsNewestFirst(): void
    {
        $albumRepository = self::createStub(AlbumRepository::class);
        // Published root albums in 2020 and 2024 (four association years apart), plus a second 2024 album. The empty
        // years in between must be absent and 2024 must appear once. Association years start on 1 July, so a November
        // date falls in its own calendar year.
        $albumRepository->method('getPublishedRootAlbumStartDates')->willReturn([
            ['startDateTime' => new DateTime('2024-11-01')],
            ['startDateTime' => new DateTime('2020-11-01')],
            ['startDateTime' => new DateTime('2024-12-15')],
        ]);

        $service = new AlbumService(
            $albumRepository,
            self::createStub(PhotoRepository::class),
            self::createStub(Security::class),
        );

        self::assertSame(
            [
                2024,
                2020,
            ],
            $service->getViewableRootAlbumYears(),
        );
    }
}
