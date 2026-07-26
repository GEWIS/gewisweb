<?php

declare(strict_types=1);

namespace App\Tests\Entity\Application;

use App\Entity\Application\MaintenanceWindow;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class MaintenanceWindowTest extends TestCase
{
    public function testAnOpenWindowIsAlwaysActive(): void
    {
        self::assertTrue(
            new MaintenanceWindow()->isActiveAt(new DateTimeImmutable('2026-01-01 00:00')),
        );
    }

    public function testAWindowIsNotActiveBeforeItsStart(): void
    {
        $window = new MaintenanceWindow();
        $window->setStartsAt(new DateTimeImmutable('2026-06-01 12:00'));

        self::assertFalse($window->isActiveAt(new DateTimeImmutable('2026-06-01 11:59')));
        self::assertTrue($window->isActiveAt(new DateTimeImmutable('2026-06-01 12:00')));
    }

    public function testAWindowIsNotActiveOnceItsEndPasses(): void
    {
        $window = new MaintenanceWindow();
        $window->setEndsAt(new DateTimeImmutable('2026-06-01 12:00'));

        self::assertTrue($window->isActiveAt(new DateTimeImmutable('2026-06-01 11:59')));
        self::assertFalse($window->isActiveAt(new DateTimeImmutable('2026-06-01 12:00')));
    }

    public function testClashingWindowsOverlap(): void
    {
        self::assertTrue(
            $this->windowBetween(
                '2026-06-01 00:00',
                '2026-06-10 00:00',
            )
                ->overlaps($this->windowBetween('2026-06-05 00:00', '2026-06-15 00:00')),
        );
    }

    public function testBackToBackWindowsDoNotOverlap(): void
    {
        self::assertFalse(
            $this->windowBetween(
                '2026-06-01 00:00',
                '2026-06-10 00:00',
            )
                ->overlaps($this->windowBetween('2026-06-10 00:00', '2026-06-20 00:00')),
        );
    }

    public function testDisjointWindowsDoNotOverlap(): void
    {
        self::assertFalse(
            $this->windowBetween(
                '2026-06-01 00:00',
                '2026-06-05 00:00',
            )
                ->overlaps($this->windowBetween('2026-06-10 00:00', '2026-06-20 00:00')),
        );
    }

    public function testAnOpenWindowOverlapsEverything(): void
    {
        self::assertTrue(
            new MaintenanceWindow()
                ->overlaps($this->windowBetween('2026-06-01 00:00', '2026-06-05 00:00')),
        );
    }

    private function windowBetween(
        string $start,
        string $end,
    ): MaintenanceWindow {
        $window = new MaintenanceWindow();
        $window->setStartsAt(new DateTimeImmutable($start));
        $window->setEndsAt(new DateTimeImmutable($end));

        return $window;
    }
}
