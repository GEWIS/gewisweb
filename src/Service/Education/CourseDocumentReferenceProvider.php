<?php

declare(strict_types=1);

namespace App\Service\Education;

use App\Repository\Education\CourseDocumentRepository;
use App\Repository\Education\CourseDocumentStagingRepository;
use App\Service\Application\FileReferenceProviderInterface;
use Override;

/**
 * Keeps an uploaded exam or summary alive while any course document, or any upload still waiting to be published,
 * points at it. The same PDF filed under two courses is one content-addressed file, so deleting one document must not
 * remove the other's source.
 */
final readonly class CourseDocumentReferenceProvider implements FileReferenceProviderInterface
{
    public function __construct(
        private CourseDocumentRepository $documentRepository,
        private CourseDocumentStagingRepository $stagingRepository,
    ) {
    }

    #[Override]
    public function references(string $path): bool
    {
        return $this->documentRepository->isPathReferenced($path)
            || $this->stagingRepository->isPathReferenced($path);
    }
}
