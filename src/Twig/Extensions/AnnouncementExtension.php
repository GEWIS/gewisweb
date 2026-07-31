<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use App\Entity\Application\Announcement;
use App\Repository\Application\AnnouncementRepository;
use DateTimeImmutable;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Exposes `active_announcements()`: the sticky announcements whose end date has not passed, for the top-of-page banner.
 */
class AnnouncementExtension extends AbstractExtension
{
    public function __construct(private readonly AnnouncementRepository $repository)
    {
    }

    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'active_announcements',
                $this->activeAnnouncements(...),
            ),
        ];
    }

    /**
     * @return Announcement[]
     */
    public function activeAnnouncements(): array
    {
        return $this->repository->findActive(new DateTimeImmutable());
    }
}
