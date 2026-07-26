<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use App\Entity\Application\Enums\MaintenanceStatus;
use App\Service\Application\MaintenanceStatusProvider;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Exposes `maintenance_status()`: the app-level maintenance status in force right now, so the layout can show the
 * read-only banner.
 */
class MaintenanceExtension extends AbstractExtension
{
    public function __construct(private readonly MaintenanceStatusProvider $maintenanceStatus)
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
                'maintenance_status',
                $this->maintenanceStatus(...),
            ),
        ];
    }

    public function maintenanceStatus(): MaintenanceStatus
    {
        return $this->maintenanceStatus->status();
    }
}
