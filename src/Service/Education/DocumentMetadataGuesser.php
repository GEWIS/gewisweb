<?php

declare(strict_types=1);

namespace App\Service\Education;

use App\Entity\Application\Enums\Languages;
use App\Entity\Education\Enums\CourseDocumentTypes;
use App\Entity\Education\Enums\ExamTypes;
use App\ViewModel\Education\GuessedDocumentMetadata;
use DateTime;

use function array_keys;
use function explode;
use function pathinfo;
use function preg_match;
use function str_contains;
use function str_replace;
use function strlen;
use function strtolower;
use function strtoupper;

use const PATHINFO_FILENAME;

/**
 * Exams arrive from the department named to no particular standard, but they do tend to carry the course code and the
 * date somewhere. Everything here is a guess an administrator corrects before publishing, so being wrong is cheap.
 */
final readonly class DocumentMetadataGuesser
{
    /**
     * A course code: a digit, a letter, then three or four more alphanumerics. `2IL50`, `2WBB0`, `2IMF20`.
     *
     * It has to stand on its own rather than run into a neighbouring word, because the length is not fixed and a code
     * jammed against one would swallow its first letter. `exam2WF50final` yields nothing rather than `2WF50F`, which
     * would look right at a glance and name a course that does not exist.
     */
    private const string COURSE_CODE = '/(?<![0-9a-zA-Z])\d[a-zA-Z][0-9a-zA-Z]{3,4}(?![0-9a-zA-Z])/';

    /** A whole date written without separators, as `20240615`. Tried before the parts, which would misread it. */
    private const string COMPACT_DATE = '/(?<![0-9])((?:19|20)\d{2})(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])(?![0-9])/';

    /** A four-digit year in a plausible range for an exam. */
    private const string YEAR = '/(?:19|20)\d{2}/';

    /** A month, once the year has been taken out so its digits cannot be mistaken for one. */
    private const string MONTH = '/(?<![0-9])(0[1-9]|1[0-2])(?![0-9])/';

    /** A day, once the year and month have been taken out. */
    private const string DAY = '/(?<![0-9])(0[1-9]|[12]\d|3[01])(?![0-9])/';

    /** Filenames that say what they hold. Checked in order, so the more specific ones come first. */
    private const array TYPE_MARKERS = [
        'antwoord' => ExamTypes::Answers,
        'answer' => ExamTypes::Answers,
        'solution' => ExamTypes::Answers,
        'uitwerking' => ExamTypes::Answers,
        'tussentoets' => ExamTypes::Interim,
        'interim' => ExamTypes::Interim,
        'midterm' => ExamTypes::Interim,
        'tentamen' => ExamTypes::Final,
        'exam' => ExamTypes::Final,
        'final' => ExamTypes::Final,
    ];

    private const array SUMMARY_MARKERS = [
        'samenvatting',
        'summary',
        'cheatsheet',
        'cheat-sheet',
    ];

    /** A name is at least this long and contains no digits, which is how an author is picked out of a filename. */
    private const int MINIMUM_NAME_LENGTH = 4;

    public function guess(string $filename): GuessedDocumentMetadata
    {
        $stem = pathinfo(
            $filename,
            PATHINFO_FILENAME,
        );
        $lowered = strtolower($stem);

        $code = $this->matchOrNull(
            self::COURSE_CODE,
            $stem,
        );
        $type = $this->guessType($lowered);

        return new GuessedDocumentMetadata(
            courseCode: null !== $code ? strtoupper($code) : null,
            date: $this->guessDate(
                $stem,
                $code,
            ),
            language: str_contains(
                $lowered,
                'nl',
            ) || str_contains(
                $lowered,
                'dutch',
            )
                ? Languages::Dutch
                : Languages::English,
            type: $type,
            examType: CourseDocumentTypes::Exam === $type ? $this->guessExamType($lowered) : null,
            author: CourseDocumentTypes::Summary === $type ? $this->guessAuthor($stem) : null,
        );
    }

    /**
     * The parts are taken out as they are found, so the year's digits cannot be read again as a month.
     */
    private function guessDate(
        string $stem,
        ?string $code,
    ): ?DateTime {
        // The course code is full of digits that would otherwise be read as a date.
        $remainder = null !== $code
            ? str_replace(
                $code,
                '',
                $stem,
            )
            : $stem;

        if (
            1 === preg_match(
                self::COMPACT_DATE,
                $remainder,
                $compact,
            )
        ) {
            return $this->toDate(
                $compact[1],
                $compact[2],
                $compact[3],
            );
        }

        $year = $this->matchOrNull(
            self::YEAR,
            $remainder,
        );
        if (null === $year) {
            return null;
        }

        $remainder = str_replace(
            $year,
            '',
            $remainder,
        );

        $month = $this->matchOrNull(
            self::MONTH,
            $remainder,
        ) ?? '01';
        $remainder = str_replace(
            $month,
            '',
            $remainder,
        );

        $day = $this->matchOrNull(
            self::DAY,
            $remainder,
        ) ?? '01';

        return $this->toDate(
            $year,
            $month,
            $day,
        );
    }

    private function toDate(
        string $year,
        string $month,
        string $day,
    ): ?DateTime {
        $date = DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $year . '-' . $month . '-' . $day . ' 00:00:00',
        );

        return false !== $date
            ? $date
            : null;
    }

    private function guessType(string $lowered): CourseDocumentTypes
    {
        foreach (self::SUMMARY_MARKERS as $marker) {
            if (
                str_contains(
                    $lowered,
                    $marker,
                )
            ) {
                return CourseDocumentTypes::Summary;
            }
        }

        return CourseDocumentTypes::Exam;
    }

    private function guessExamType(string $lowered): ExamTypes
    {
        foreach (self::TYPE_MARKERS as $marker => $type) {
            if (
                str_contains(
                    $lowered,
                    $marker,
                )
            ) {
                return $type;
            }
        }

        return ExamTypes::Final;
    }

    /**
     * A summary usually carries its author's name as one of the dot- or underscore-separated parts of its filename.
     * Anything long enough and free of digits is taken for one.
     */
    private function guessAuthor(string $stem): ?string
    {
        foreach (
            explode(
                ' ',
                str_replace(
                    [
                        '.',
                        '_',
                        '-',
                    ],
                    ' ',
                    $stem,
                ),
            ) as $part
        ) {
            if (
                strlen($part) < self::MINIMUM_NAME_LENGTH
                || 1 === preg_match(
                    '/\d/',
                    $part,
                )
                || null !== $this->markerFor(strtolower($part))
            ) {
                continue;
            }

            return $part;
        }

        return null;
    }

    private function markerFor(string $part): ?string
    {
        foreach ([...self::SUMMARY_MARKERS, ...array_keys(self::TYPE_MARKERS)] as $marker) {
            if (
                str_contains(
                    $part,
                    $marker,
                )
            ) {
                return $marker;
            }
        }

        return null;
    }

    private function matchOrNull(
        string $pattern,
        string $subject,
    ): ?string {
        if (
            1 !== preg_match(
                $pattern,
                $subject,
                $matches,
            )
        ) {
            return null;
        }

        return $matches[0];
    }
}
