<?php

declare(strict_types=1);

namespace App\Tests\Service\Application;

use App\Entity\Application\Enums\MaintenanceStatus;
use App\Entity\Application\MaintenanceWindow;
use App\Repository\Application\MaintenanceWindowRepository;
use App\Service\Application\MaintenanceStatusProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class MaintenanceStatusProviderTest extends TestCase
{
    public function testTheActiveWindowIsQueriedOncePerRequest(): void
    {
        $window = new MaintenanceWindow();
        $window->setStatus(MaintenanceStatus::Full);

        $repository = $this->createMock(MaintenanceWindowRepository::class);
        $repository->expects(self::once())
            ->method('findActiveAt')
            ->willReturn($window);

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $provider = new MaintenanceStatusProvider(
            $repository,
            $requestStack,
        );

        self::assertSame(
            $window,
            $provider->activeWindow(),
        );
        self::assertSame(
            MaintenanceStatus::Full,
            $provider->status(),
        );
        self::assertSame(
            $window,
            $provider->activeWindow(),
        );
    }

    public function testTheAbsenceOfAWindowIsCachedToo(): void
    {
        $repository = $this->createMock(MaintenanceWindowRepository::class);
        $repository->expects(self::once())
            ->method('findActiveAt')
            ->willReturn(null);

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $provider = new MaintenanceStatusProvider(
            $repository,
            $requestStack,
        );

        self::assertSame(
            MaintenanceStatus::None,
            $provider->status(),
        );
        self::assertNull($provider->activeWindow());
    }

    public function testWithoutARequestEachCallQueries(): void
    {
        $repository = $this->createMock(MaintenanceWindowRepository::class);
        $repository->expects(self::exactly(2))
            ->method('findActiveAt')
            ->willReturn(null);

        $provider = new MaintenanceStatusProvider(
            $repository,
            new RequestStack(),
        );

        $provider->activeWindow();
        $provider->activeWindow();
    }
}
