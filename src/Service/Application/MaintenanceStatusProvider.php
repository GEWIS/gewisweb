<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\Enums\MaintenanceStatus;
use App\Entity\Application\MaintenanceWindow;
use App\Repository\Application\MaintenanceWindowRepository;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The maintenance window in force right now, resolved once per request. The request listener, the login checker and
 * the Twig layer all ask for it, so the answer is memoised on the request's attributes (safe under FrankenPHP's worker
 * mode, where a plain service property would leak between requests) to keep it to a single query.
 */
final readonly class MaintenanceStatusProvider
{
    private const string ATTRIBUTE = '_maintenance_window';

    public function __construct(
        private MaintenanceWindowRepository $repository,
        private RequestStack $requestStack,
    ) {
    }

    public function activeWindow(): ?MaintenanceWindow
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return $this->repository->findActiveAt(new DateTimeImmutable());
        }

        if (!$request->attributes->has(self::ATTRIBUTE)) {
            $request->attributes->set(
                self::ATTRIBUTE,
                $this->repository->findActiveAt(new DateTimeImmutable()),
            );
        }

        $window = $request->attributes->get(self::ATTRIBUTE);

        return $window instanceof MaintenanceWindow
            ? $window
            : null;
    }

    public function status(): MaintenanceStatus
    {
        return $this->activeWindow()?->getStatus() ?? MaintenanceStatus::None;
    }
}
