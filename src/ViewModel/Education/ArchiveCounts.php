<?php

declare(strict_types=1);

namespace App\ViewModel\Education;

final readonly class ArchiveCounts
{
    public function __construct(
        public int $courses,
        public int $documents,
        public int $coursesWithSummaries,
        public int $coursesWithoutMaterial,
    ) {
    }
}
