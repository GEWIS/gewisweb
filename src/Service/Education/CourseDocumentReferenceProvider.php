<?php

declare(strict_types=1);

namespace App\Service\Education;

use App\Repository\Education\CourseDocumentRepository;
use App\Service\Application\FileReferenceProviderInterface;
use Override;

/**
 * Keeps an uploaded exam or summary alive while any course document still points at it. The same PDF filed under two
 * courses is one content-addressed file, so deleting one document must not remove the other's source.
 */
final readonly class CourseDocumentReferenceProvider implements FileReferenceProviderInterface
{
    public function __construct(
        private CourseDocumentRepository $documentRepository,
    ) {
    }

    #[Override]
    public function references(string $path): bool
    {
        return $this->documentRepository->isPathReferenced($path);
    }
}
