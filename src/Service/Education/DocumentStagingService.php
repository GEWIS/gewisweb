<?php

declare(strict_types=1);

namespace App\Service\Education;

use App\Entity\Application\Enums\StorageNamespace;
use App\Entity\Education\CourseDocumentStaging;
use App\Entity\Education\Enums\CourseDocumentTypes;
use App\Entity\Education\Enums\ExamTypes;
use App\Entity\Education\Exam;
use App\Entity\Education\Summary;
use App\Entity\User\User;
use App\Message\Education\FlattenCourseDocumentMessage;
use App\Repository\Education\CourseRepository;
use App\Service\Application\FileStorage;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;

use function file_put_contents;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * A batch of exams arrives named however the sender felt like naming them, so an upload is not filed straight away: it
 * lands in staging with everything guessed from its filename, and is published once an administrator has confirmed the
 * guesses. Nothing is visible to a member in between.
 */
final readonly class DocumentStagingService
{
    public function __construct(
        private FileStorage $fileStorage,
        private DocumentMetadataGuesser $guesser,
        private CourseRepository $courseRepository,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function stage(
        UploadedFile $file,
        ?User $user,
    ): CourseDocumentStaging {
        $originalName = $file->getClientOriginalName();
        $guess = $this->guesser->guess($originalName);

        $staged = new CourseDocumentStaging();
        $staged->setOriginalFilename($originalName);
        $staged->setUploadedBy($user);
        $staged->setUploadedAt(new DateTime());
        $staged->setCourseCode($guess->courseCode);
        $staged->setDate($guess->date);
        $staged->setLanguage($guess->language);
        $staged->setType($guess->type);
        $staged->setExamType($guess->examType);
        $staged->setAuthor($guess->author);
        $staged->setPath($this->fileStorage->store(
            StorageNamespace::EducationDocument,
            $file->getPathname(),
            // The course is only a guess at this point, so a staged file is filed under a holding scope and moves when
            // it is published and the course is known.
            'staging',
        )->path);

        $this->entityManager->persist($staged);
        $this->entityManager->flush();

        return $staged;
    }

    /**
     * @throws RuntimeException if the staged row does not name a course that exists.
     */
    public function publish(CourseDocumentStaging $staged): void
    {
        $code = $staged->getCourseCode();
        $course = null !== $code
            ? $this->courseRepository->find($code)
            : null;

        if (null === $course) {
            throw new RuntimeException('A staged upload cannot be published without an existing course.');
        }

        if (CourseDocumentTypes::Summary === $staged->getType()) {
            $summary = new Summary();
            $summary->setAuthor($staged->getAuthor());
            $document = $summary;
        } else {
            $exam = new Exam();
            $exam->setExamType($staged->getExamType() ?? ExamTypes::Final);
            $document = $exam;
        }

        $document->setCourse($course);
        $document->setDate($staged->getDate() ?? new DateTime());
        $document->setLanguage($staged->getLanguage());
        $document->setScanned($staged->getScanned());
        // The file is re-filed under the course now that it is known, which also gives it its final content-addressed
        // path; the staged copy is dropped with the row.
        $document->setPath($this->refile(
            $staged->getPath(),
            $course->getCode(),
        ));

        $this->entityManager->persist($document);
        $this->entityManager->remove($staged);
        $this->entityManager->flush();

        $this->fileStorage->remove($staged->getPath());

        $this->messageBus->dispatch(new FlattenCourseDocumentMessage($document->getId() ?? 0));
    }

    public function discard(CourseDocumentStaging $staged): void
    {
        $path = $staged->getPath();

        $this->entityManager->remove($staged);
        $this->entityManager->flush();

        $this->fileStorage->remove($path);
    }

    private function refile(
        string $stagedPath,
        string $code,
    ): string {
        $bytes = $this->fileStorage->read($stagedPath);

        $localPath = tempnam(
            sys_get_temp_dir(),
            'education-publish-',
        );
        if (false === $localPath) {
            throw new RuntimeException('Could not create a temporary file to publish an upload.');
        }

        file_put_contents(
            $localPath,
            $bytes,
        );

        try {
            return $this->fileStorage->store(
                StorageNamespace::EducationDocument,
                $localPath,
                $code,
            )->path;
        } finally {
            unlink($localPath);
        }
    }
}
