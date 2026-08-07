<?php

declare(strict_types=1);

namespace App\ViewModel\Education;

use App\Entity\Application\Enums\Languages;
use App\Entity\Education\Enums\CourseDocumentTypes;
use App\Entity\Education\Enums\ExamTypes;
use DateTime;

/**
 * Every field is a guess an administrator confirms or corrects before anything is published, so any of them may be
 * wrong and the ones the filename said nothing about are null.
 */
final readonly class GuessedDocumentMetadata
{
    public function __construct(
        public ?string $courseCode,
        public ?DateTime $date,
        public Languages $language,
        public CourseDocumentTypes $type,
        public ?ExamTypes $examType,
        public ?string $author,
    ) {
    }
}
