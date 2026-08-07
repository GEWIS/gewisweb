<?php

declare(strict_types=1);

namespace App\Entity\Education\Enums;

use Symfony\Component\Translation\TranslatableMessage;

/**
 * The ways the course archive can be narrowed down. Courses with nothing in them stay listed rather than being
 * hidden, so it is visible what the archive is missing, which is what {@see self::Empty} is for.
 */
enum CourseFilter: string
{
    case All = 'all';
    case WithSummaries = 'summaries';
    case WithExams = 'exams';
    case Empty = 'empty';

    public function label(): TranslatableMessage
    {
        return match ($this) {
            self::All => new TranslatableMessage('All courses'),
            self::WithSummaries => new TranslatableMessage('With summaries'),
            self::WithExams => new TranslatableMessage('With exams'),
            self::Empty => new TranslatableMessage('Nothing yet'),
        };
    }
}
