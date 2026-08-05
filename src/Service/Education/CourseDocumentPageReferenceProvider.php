<?php

declare(strict_types=1);

namespace App\Service\Education;

use App\Repository\Education\CourseDocumentPageRepository;
use App\Service\Application\FileReferenceProviderInterface;
use Override;

/**
 * Keeps a rendered page alive while any course document still points at it. Page images are content-addressed, so two
 * documents holding an identical page (a blank sheet, a shared front page, the same exam filed under two courses) share
 * one stored file, and deleting one of them must not take the other's pages with it.
 */
final readonly class CourseDocumentPageReferenceProvider implements FileReferenceProviderInterface
{
    public function __construct(
        private CourseDocumentPageRepository $pageRepository,
    ) {
    }

    #[Override]
    public function references(string $path): bool
    {
        return $this->pageRepository->isPathReferenced($path);
    }
}
