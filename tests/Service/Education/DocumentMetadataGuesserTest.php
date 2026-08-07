<?php

declare(strict_types=1);

namespace App\Tests\Service\Education;

use App\Entity\Application\Enums\Languages;
use App\Entity\Education\Enums\CourseDocumentTypes;
use App\Entity\Education\Enums\ExamTypes;
use App\Service\Education\DocumentMetadataGuesser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * What can be read out of a filename, pinned against the shapes the department and members actually send. The previous
 * site did this inline and untested, and the whole point of guessing is that it is right often enough to save typing;
 * a change that quietly stops recognising course codes would only show up as somebody filling in dozens of forms.
 */
final class DocumentMetadataGuesserTest extends TestCase
{
    #[DataProvider('courseCodes')]
    public function testItReadsTheCourseCode(
        string $filename,
        ?string $expected,
    ): void {
        self::assertSame(
            $expected,
            new DocumentMetadataGuesser()->guess($filename)->courseCode,
        );
    }

    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function courseCodes(): iterable
    {
        yield 'plain' => [
            '2IL50-tentamen-2024-06-15.pdf',
            '2IL50',
        ];

        yield 'six characters' => [
            '2IMF20_exam_2023.pdf',
            '2IMF20',
        ];

        yield 'lower case' => [
            '2wbb0 exam 2022.pdf',
            '2WBB0',
        ];

        // Undelimited, so it is left alone: a greedy match here would produce 2WF50F, which names no course but looks
        // plausible enough to be published without a second glance.
        yield 'undelimited' => [
            'exam2WF50final.pdf',
            null,
        ];

        yield 'absent' => [
            'tentamen-2024.pdf',
            null,
        ];
    }

    #[DataProvider('dates')]
    public function testItReadsTheDate(
        string $filename,
        ?string $expected,
    ): void {
        $date = new DocumentMetadataGuesser()->guess($filename)->date;

        self::assertSame(
            $expected,
            $date?->format('Y-m-d'),
        );
    }

    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function dates(): iterable
    {
        yield 'full date' => [
            '2IL50-tentamen-2024-06-15.pdf',
            '2024-06-15',
        ];

        yield 'no separators' => [
            '2IL50 20240615 exam.pdf',
            '2024-06-15',
        ];

        yield 'year only' => [
            '2IL50-exam-2024.pdf',
            '2024-01-01',
        ];

        yield 'no date' => [
            '2IL50-exam.pdf',
            null,
        ];
    }

    /**
     * The course code is full of digits, and reading them as a date is the mistake this is guarding against.
     */
    public function testTheCourseCodeIsNotMistakenForADate(): void
    {
        $guess = new DocumentMetadataGuesser()->guess('2IL50-2024.pdf');

        self::assertSame(
            '2IL50',
            $guess->courseCode,
        );
        self::assertSame(
            '2024-01-01',
            $guess->date?->format('Y-m-d'),
        );
    }

    #[DataProvider('kinds')]
    public function testItReadsWhatKindOfDocumentItIs(
        string $filename,
        CourseDocumentTypes $type,
        ?ExamTypes $examType,
    ): void {
        $guess = new DocumentMetadataGuesser()->guess($filename);

        self::assertSame(
            $type,
            $guess->type,
        );
        self::assertSame(
            $examType,
            $guess->examType,
        );
    }

    /**
     * @return iterable<string, array{string, CourseDocumentTypes, ?ExamTypes}>
     */
    public static function kinds(): iterable
    {
        yield 'dutch exam' => [
            '2IL50-tentamen-2024.pdf',
            CourseDocumentTypes::Exam,
            ExamTypes::Final,
        ];

        yield 'interim' => [
            '2IL50-tussentoets-2024.pdf',
            CourseDocumentTypes::Exam,
            ExamTypes::Interim,
        ];

        yield 'midterm' => [
            '2IL50-midterm-2024.pdf',
            CourseDocumentTypes::Exam,
            ExamTypes::Interim,
        ];

        yield 'answers' => [
            '2IL50-antwoorden-2024.pdf',
            CourseDocumentTypes::Exam,
            ExamTypes::Answers,
        ];

        yield 'solutions' => [
            '2IL50-solutions-2024.pdf',
            CourseDocumentTypes::Exam,
            ExamTypes::Answers,
        ];

        yield 'summary' => [
            '2IL50-samenvatting-2024.pdf',
            CourseDocumentTypes::Summary,
            null,
        ];

        // Nothing in the name says what it is, and an exam is what the archive mostly holds.
        yield 'unmarked' => [
            '2IL50-2024.pdf',
            CourseDocumentTypes::Exam,
            ExamTypes::Final,
        ];
    }

    public function testItReadsTheLanguage(): void
    {
        self::assertSame(
            Languages::Dutch,
            new DocumentMetadataGuesser()->guess('2IL50-nl-2024.pdf')->language,
        );
        self::assertSame(
            Languages::English,
            new DocumentMetadataGuesser()->guess('2IL50-2024.pdf')->language,
        );
    }

    /**
     * A summary usually carries the name of whoever wrote it. An exam does not, and guessing one there would put a
     * random word in front of a member as an author.
     */
    public function testItPicksAnAuthorOutOfASummaryOnly(): void
    {
        self::assertSame(
            'Bakker',
            new DocumentMetadataGuesser()->guess('2IL50.samenvatting.Bakker.2024.pdf')->author,
        );
        self::assertNull(new DocumentMetadataGuesser()->guess('2IL50.tentamen.2024.pdf')->author);
    }
}
