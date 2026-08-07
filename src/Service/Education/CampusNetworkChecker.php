<?php

declare(strict_types=1);

namespace App\Service\Education;

use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Only ever used to widen access to course material. Nothing else in the application decides anything on the strength
 * of a client address, and this should not become the exception.
 */
final readonly class CampusNetworkChecker
{
    /**
     * @param string[] $tueRanges subnets in CIDR notation
     */
    public function __construct(
        private RequestStack $requestStack,
        private array $tueRanges,
    ) {
    }

    public function isOnCampus(): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return false;
        }

        return $this->matches($request->getClientIp());
    }

    public function matches(?string $clientIp): bool
    {
        if (
            null === $clientIp
            || [] === $this->tueRanges
        ) {
            return false;
        }

        return IpUtils::checkIp(
            $clientIp,
            $this->tueRanges,
        );
    }
}
