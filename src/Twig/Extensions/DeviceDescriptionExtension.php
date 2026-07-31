<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use App\Service\Application\DeviceDescription;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Exposes `device_description()`, so the session lists and the notification centre name a device the same way.
 */
class DeviceDescriptionExtension extends AbstractExtension
{
    public function __construct(private readonly DeviceDescription $deviceDescription)
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
                'device_description',
                $this->deviceDescription->render(...),
            ),
        ];
    }
}
