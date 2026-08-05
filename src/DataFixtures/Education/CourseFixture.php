<?php

declare(strict_types=1);

namespace App\DataFixtures\Education;

use App\Entity\Application\Enums\Languages;
use App\Entity\Application\Enums\StorageNamespace;
use App\Entity\Education\Course;
use App\Entity\Education\CourseDocument;
use App\Entity\Education\CourseDocumentPage;
use App\Entity\Education\Enums\DocumentFlattenStatus;
use App\Entity\Education\Enums\ExamTypes;
use App\Entity\Education\Exam;
use App\Entity\Education\Summary;
use App\Service\Application\FileStorage;
use App\Service\Application\ImageManagerProvider;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use FPDF;
use Intervention\Image\Encoders\JpegEncoder;
use Override;
use RuntimeException;

use function file_put_contents;
use function sprintf;
use function strval;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * Enough shape to exercise every state the pages have to render: courses with both kinds of material, courses with only
 * one, a course that is listed but empty, and a pair of courses linked as similar so the empty one has somewhere to
 * point. Documents are seeded already rasterized, so they are downloadable without a worker having run.
 */
class CourseFixture extends Fixture
{
    private Generator $faker;

    public function __construct(
        private readonly FileStorage $fileStorage,
        private readonly ImageManagerProvider $imageManagerProvider,
    ) {
    }

    #[Override]
    public function load(ObjectManager $manager): void
    {
        $this->faker = FakerFactory::create();

        $dataStructures = $this->createCourse(
            $manager,
            '2IL50',
            'Data structures',
        );
        $algorithms = $this->createCourse(
            $manager,
            '2IPC0',
            'Data Structures and Algorithms',
        );
        $discrete = $this->createCourse(
            $manager,
            '2WF50',
            'Discrete Mathematics',
        );
        $logic = $this->createCourse(
            $manager,
            '2ITX0',
            'Applied Logic',
        );

        // The renumbered pair: 2ITX0 has nothing of its own, and the course it replaced does.
        $logic->addSimilarCourseTo($dataStructures);

        $this->createSummary(
            $manager,
            $dataStructures,
            new DateTime('2025-11-10'),
            Languages::English,
        );
        $this->createSummary(
            $manager,
            $dataStructures,
            new DateTime('2025-09-02'),
            Languages::English,
        );
        $this->createExam(
            $manager,
            $dataStructures,
            ExamTypes::Final,
            new DateTime('2025-01-22'),
            Languages::English,
        );
        $this->createExam(
            $manager,
            $dataStructures,
            ExamTypes::Interim,
            new DateTime('2024-11-04'),
            Languages::English,
        );

        $this->createSummary(
            $manager,
            $discrete,
            new DateTime('2025-04-18'),
            Languages::Dutch,
        );
        $this->createExam(
            $manager,
            $discrete,
            ExamTypes::Final,
            new DateTime('2025-04-09'),
            Languages::English,
        );

        // Only exams, so the "with summaries" filter has something it excludes.
        $this->createExam(
            $manager,
            $algorithms,
            ExamTypes::Answers,
            new DateTime('2024-10-14'),
            Languages::English,
        );

        $manager->flush();
    }

    private function createCourse(
        ObjectManager $manager,
        string $code,
        string $name,
    ): Course {
        $course = new Course();
        $course->setCode($code);
        $course->setName($name);

        $manager->persist($course);

        return $course;
    }

    private function createSummary(
        ObjectManager $manager,
        Course $course,
        DateTime $date,
        Languages $language,
    ): void {
        $summary = new Summary();
        $summary->setAuthor($this->faker->name());

        $this->finishDocument(
            $manager,
            $summary,
            $course,
            $date,
            $language,
        );
    }

    private function createExam(
        ObjectManager $manager,
        Course $course,
        ExamTypes $type,
        DateTime $date,
        Languages $language,
    ): void {
        $exam = new Exam();
        $exam->setExamType($type);

        $this->finishDocument(
            $manager,
            $exam,
            $course,
            $date,
            $language,
        );
    }

    private function finishDocument(
        ObjectManager $manager,
        CourseDocument $document,
        Course $course,
        DateTime $date,
        Languages $language,
    ): void {
        $document->setCourse($course);
        $document->setDate($date);
        $document->setLanguage($language);
        $document->setScanned(false);
        $document->setPath($this->storePdf(
            $course->getCode(),
            $date,
        ));
        $document->setFlattenStatus(DocumentFlattenStatus::Ready);
        $document->setFlattenedAt(new DateTime());

        // Flushed early because a page is stored under the document's id, the way the flattener files it.
        $manager->persist($document);
        $manager->flush();

        $page = new CourseDocumentPage();
        $page->setPageNumber(1);
        $page->setPath($this->storePage(strval($document->getId())));
        $page->setWidth(1240);
        $page->setHeight(1754);

        $document->addPage($page);

        $manager->persist($page);
    }

    /** A real one-page PDF, so a seeded document can be run through the pipeline by hand. */
    private function storePdf(
        string $code,
        DateTime $date,
    ): string {
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont(
            'Helvetica',
            '',
            14,
        );
        $pdf->Text(
            20,
            40,
            sprintf(
                '%s, %s',
                $code,
                $date->format('j F Y'),
            ),
        );

        return $this->store(
            $pdf->Output('S'),
            StorageNamespace::EducationDocument,
            $code,
        );
    }

    /** The bytes only have to be a JPEG; a seeded archive is for the pages that list documents. */
    private function storePage(string $code): string
    {
        $bytes = $this->imageManagerProvider->create()
            ->createImage(
                1240,
                1754,
            )
            ->encode(new JpegEncoder(quality: 70))
            ->toString();

        return $this->store(
            $bytes,
            StorageNamespace::EducationDocumentPage,
            $code,
        );
    }

    private function store(
        string $bytes,
        StorageNamespace $namespace,
        string $scope,
    ): string {
        $temporaryFile = tempnam(
            sys_get_temp_dir(),
            'fixture-education-',
        );

        if (false === $temporaryFile) {
            throw new RuntimeException('Could not create a temporary file for a fixture document.');
        }

        file_put_contents(
            $temporaryFile,
            $bytes,
        );

        try {
            return $this->fileStorage->store(
                $namespace,
                $temporaryFile,
                $scope,
            )->path;
        } finally {
            unlink($temporaryFile);
        }
    }
}
