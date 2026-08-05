<?php

declare(strict_types=1);

namespace App\ViewModel\Career\Admin;

/**
 * How much sits behind each tab of the career overview, so the tab bar can say so without the templates counting
 * anything themselves.
 */
final readonly class OverviewCounts
{
    public function __construct(
        public int $companies,
        public int $packages,
        public int $vacancies,
    ) {
    }
}
