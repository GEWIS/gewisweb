<?php

declare(strict_types=1);

namespace App\Service\Education;

use App\Entity\Education\Course;
use App\Entity\Education\CourseDocument;
use App\Service\Application\FileStorage;
use Doctrine\ORM\EntityManagerInterface;

use function strtoupper;

final readonly class CourseAdminService
{
    public function __construct(
        private FileStorage $fileStorage,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Course $course): void
    {
        $course->setCode(strtoupper($course->getCode()));

        $this->entityManager->persist($course);
        $this->entityManager->flush();
    }

    public function deleteCourse(Course $course): void
    {
        foreach ($course->getDocuments() as $document) {
            $this->deleteDocument($document);
        }

        $course->clearSimilarCoursesTo();

        $this->entityManager->remove($course);
        $this->entityManager->flush();
    }

    /**
     * The rows go first and are flushed, so the reference providers see committed state and only bytes that nothing
     * points at any more are unlinked. A page image can be shared with another document.
     */
    public function deleteDocument(CourseDocument $document): void
    {
        $pagePaths = [];
        foreach ($document->getPages() as $page) {
            $pagePaths[] = $page->getPath();
            $this->entityManager->remove($page);
        }

        $documentPath = $document->getPath();

        $this->entityManager->remove($document);
        $this->entityManager->flush();

        foreach ($pagePaths as $path) {
            $this->fileStorage->remove($path);
        }

        $this->fileStorage->remove($documentPath);
    }
}
