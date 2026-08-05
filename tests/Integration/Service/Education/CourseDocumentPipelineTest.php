<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Education;

use App\Entity\Application\Enums\Languages;
use App\Entity\Application\Enums\StorageNamespace;
use App\Entity\Education\Course;
use App\Entity\Education\CourseDocument;
use App\Entity\Education\Enums\DocumentFlattenStatus;
use App\Entity\Education\Enums\DownloadStatus;
use App\Entity\Education\Enums\ExamTypes;
use App\Entity\Education\Exam;
use App\Service\Application\FileStorage;
use App\Service\Education\CourseDocumentDownloadService;
use App\Service\Education\CourseDocumentFlattener;
use App\Tests\Integration\DatabaseTestCase;
use DateTime;
use FPDF;
use Override;
use RuntimeException;
use Symfony\Component\Process\Process;

use function file_put_contents;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * The whole point of the pipeline, asserted end to end: what a member downloads is rebuilt from images, so no text from
 * the document they asked for is in it, and the only text that is there is the marker that identifies where the file
 * came from.
 *
 * The source document is generated here rather than committed, so the sentence the test looks for is one this test put
 * in and nothing else could have.
 */
final class CourseDocumentPipelineTest extends DatabaseTestCase
{
    private const string SECRET_SENTENCE = 'Prove the quadratic residue theorem for every odd prime.';

    private FileStorage $fileStorage;
    private CourseDocumentFlattener $flattener;
    private CourseDocumentDownloadService $downloadService;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->fileStorage = self::getContainer()->get(FileStorage::class);
        $this->flattener = self::getContainer()->get(CourseDocumentFlattener::class);
        $this->downloadService = self::getContainer()->get(CourseDocumentDownloadService::class);
    }

    public function testARebuiltDownloadCarriesTheTagAndNoneOfTheOriginalText(): void
    {
        $document = $this->createDocument(3);

        $this->flattener->flatten($document);

        self::assertSame(
            DocumentFlattenStatus::Ready,
            $document->getFlattenStatus(),
        );
        self::assertSame(
            3,
            $document->getPageCount(),
            'Every page of the source should have been rendered.',
        );
        self::assertTrue($document->isDownloadable());

        $download = $this->downloadService->request(
            $document,
            null,
            '131.155.1.1',
        );
        $this->downloadService->build($download);

        self::assertSame(
            DownloadStatus::Ready,
            $download->getStatus(),
        );

        $path = $download->getPath();
        self::assertNotNull($path);

        $text = $this->extractText($this->fileStorage->read($path));

        self::assertStringNotContainsString(
            self::SECRET_SENTENCE,
            $text,
            'The rebuilt document must not carry any selectable text from the original.',
        );
        self::assertStringContainsString(
            $download->getReference(),
            $text,
            'The machine-readable tag must name the download it was built for.',
        );
    }

    /**
     * A document that poppler cannot read is a bad upload, not a broken queue: it is recorded as failed and stays
     * undownloadable rather than taking the worker down with it.
     */
    public function testAnUnreadableDocumentIsNotDownloadable(): void
    {
        $course = $this->createCourse();

        $temporaryFile = $this->temporaryFile();
        file_put_contents(
            $temporaryFile,
            "%PDF-1.4\nnot actually a pdf\n%%EOF\n",
        );

        $document = new Exam();
        $document->setCourse($course);
        $document->setDate(new DateTime('2026-01-15'));
        $document->setLanguage(Languages::English);
        $document->setExamType(ExamTypes::Final);
        $document->setScanned(false);
        $document->setPath($this->fileStorage->store(
            StorageNamespace::EducationDocument,
            $temporaryFile,
            $course->getCode(),
        )->path);
        unlink($temporaryFile);

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        $this->flattener->markFailed(
            $document,
            'unreadable',
        );

        self::assertSame(
            DocumentFlattenStatus::Failed,
            $document->getFlattenStatus(),
        );
        self::assertFalse($document->isDownloadable());
    }

    private function createDocument(int $pages): CourseDocument
    {
        $course = $this->createCourse();

        $pdf = new FPDF();
        for ($page = 1; $page <= $pages; $page++) {
            $pdf->AddPage();
            $pdf->SetFont(
                'Helvetica',
                '',
                14,
            );
            $pdf->Text(
                20,
                40,
                self::SECRET_SENTENCE,
            );
        }

        $temporaryFile = $this->temporaryFile();
        file_put_contents(
            $temporaryFile,
            $pdf->Output('S'),
        );

        $document = new Exam();
        $document->setCourse($course);
        $document->setDate(new DateTime('2026-01-15'));
        $document->setLanguage(Languages::English);
        $document->setExamType(ExamTypes::Final);
        $document->setScanned(false);
        $document->setPath($this->fileStorage->store(
            StorageNamespace::EducationDocument,
            $temporaryFile,
            $course->getCode(),
        )->path);
        unlink($temporaryFile);

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        return $document;
    }

    private function createCourse(): Course
    {
        $course = new Course();
        $course->setCode('2PIPE0');
        $course->setName('Document pipeline');

        $this->entityManager->persist($course);
        $this->entityManager->flush();

        return $course;
    }

    /**
     * The text layer of a PDF, as an external platform's detector would read it.
     */
    private function extractText(string $pdf): string
    {
        $temporaryFile = $this->temporaryFile();
        file_put_contents(
            $temporaryFile,
            $pdf,
        );

        $process = new Process([
            'pdftotext',
            $temporaryFile,
            '-',
        ]);
        $process->run();

        unlink($temporaryFile);

        if (!$process->isSuccessful()) {
            throw new RuntimeException('Could not read the text layer of the rebuilt document.');
        }

        return $process->getOutput();
    }

    private function temporaryFile(): string
    {
        $temporaryFile = tempnam(
            sys_get_temp_dir(),
            'education-pipeline-',
        );

        if (false === $temporaryFile) {
            throw new RuntimeException('Could not create a temporary file.');
        }

        return $temporaryFile;
    }
}
