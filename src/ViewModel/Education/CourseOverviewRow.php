<?php

declare(strict_types=1);

namespace App\ViewModel\Education;

use App\Entity\Education\Course;
use DateTime;

/**
 * Counted rather than hydrated: loading every course with its documents would fetch thousands of rows to display two
 * numbers per line.
 *
 * {@see $similarCourses} is filled only for courses that hold nothing, which is often the same course under a code it
 * had before.
 */
final readonly class CourseOverviewRow
{
    /**
     * @param Course[] $similarCourses
     */
    public function __construct(
        public string $code,
        public string $name,
        public int $summaryCount,
        public int $examCount,
        public ?DateTime $lastAddedAt,
        public array $similarCourses = [],
    ) {
    }

    public function getDocumentCount(): int
    {
        return $this->summaryCount + $this->examCount;
    }

    public function isEmpty(): bool
    {
        return 0 === $this->getDocumentCount();
    }

    /**
     * @param Course[] $similarCourses
     */
    public function withSimilarCourses(array $similarCourses): self
    {
        return new self(
            $this->code,
            $this->name,
            $this->summaryCount,
            $this->examCount,
            $this->lastAddedAt,
            $similarCourses,
        );
    }
}
