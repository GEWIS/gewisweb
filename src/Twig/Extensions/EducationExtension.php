<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use App\Service\Education\CampusNetworkChecker;
use Override;
use Symfony\Contracts\Service\ResetInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * The answer cannot change during a request, so it is worked out once and remembered. The cache is cleared between
 * requests through {@see ResetInterface}: under FrankenPHP's worker mode this service outlives a single request and
 * would otherwise answer for the previous visitor.
 */
class EducationExtension extends AbstractExtension implements ResetInterface
{
    private ?bool $onCampus = null;

    public function __construct(private readonly CampusNetworkChecker $campusNetworkChecker)
    {
    }

    #[Override]
    public function reset(): void
    {
        $this->onCampus = null;
    }

    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'on_campus_network',
                $this->onCampusNetwork(...),
            ),
        ];
    }

    public function onCampusNetwork(): bool
    {
        return $this->onCampus ??= $this->campusNetworkChecker->isOnCampus();
    }
}
