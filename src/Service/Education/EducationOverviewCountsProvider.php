<?php

declare(strict_types=1);

namespace App\Service\Education;

use App\Entity\Education\Summary;
use App\Repository\Education\CourseDocumentRepository;
use App\Repository\Education\CourseRepository;
use App\ViewModel\Education\ArchiveCounts;

/**
 * The figures on the education overview. The landing page and the navigation menu both ask for them, so the counting
 * lives here rather than in either.
 */
final readonly class EducationOverviewCountsProvider
{
    public function __construct(
        private CourseRepository $courseRepository,
        private CourseDocumentRepository $documentRepository,
    ) {
    }

    public function counts(): ArchiveCounts
    {
        $courses = $this->courseRepository->countAll();

        return new ArchiveCounts(
            courses: $courses,
            documents: $this->documentRepository->countAll(),
            coursesWithSummaries: $this->courseRepository->countWithDocuments(Summary::class),
            coursesWithoutMaterial: $courses - $this->courseRepository->countWithDocuments(),
        );
    }
}
