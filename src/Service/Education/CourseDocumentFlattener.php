<?php

declare(strict_types=1);

namespace App\Service\Education;

use App\Entity\Application\Enums\StorageNamespace;
use App\Entity\Education\CourseDocument;
use App\Entity\Education\CourseDocumentPage;
use App\Entity\Education\Enums\DocumentFlattenStatus;
use App\Service\Application\FileStorage;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;

use function bin2hex;
use function fclose;
use function fopen;
use function getimagesize;
use function random_bytes;
use function sprintf;
use function stream_copy_to_stream;
use function strval;
use function sys_get_temp_dir;

/**
 * Runs once per document, in a worker, because it is the expensive half of the pipeline: roughly 85 ms per page at
 * 150 dpi. Doing it up front keeps a download off the rasterizer entirely; it only has to composite a watermark onto
 * pages that already exist.
 */
final readonly class CourseDocumentFlattener
{
    public function __construct(
        private PdfRasterizer $rasterizer,
        private FileStorage $fileStorage,
        private EntityManagerInterface $entityManager,
        private Filesystem $filesystem,
    ) {
    }

    /**
     * Replaces any pages from an earlier run, so this is safe to repeat after a failure.
     *
     * @throws PdfRasterizerException if the document cannot be rendered.
     */
    public function flatten(CourseDocument $document): void
    {
        $document->setFlattenStatus(DocumentFlattenStatus::Processing);
        $document->setFlattenError(null);
        $this->entityManager->flush();

        $workspace = sprintf(
            '%s/course-document-%s',
            sys_get_temp_dir(),
            bin2hex(random_bytes(8)),
        );
        $this->filesystem->mkdir($workspace);

        try {
            $pdfPath = $this->copyToWorkspace(
                $document->getPath(),
                $workspace,
            );

            $renderedPages = $this->rasterizer->rasterize(
                $pdfPath,
                $workspace,
                $document->getScanned() ? PdfRasterizer::DPI_SCANNED : PdfRasterizer::DPI_DIGITAL,
            );

            $this->replacePages(
                $document,
                $renderedPages,
            );

            $document->setFlattenStatus(DocumentFlattenStatus::Ready);
            $document->setFlattenedAt(new DateTime());
            $this->entityManager->flush();
        } finally {
            $this->filesystem->remove($workspace);
        }
    }

    /**
     * Recorded on the document so an administrator can see why it never became downloadable.
     */
    public function markFailed(
        CourseDocument $document,
        string $reason,
    ): void {
        $document->setFlattenStatus(DocumentFlattenStatus::Failed);
        $document->setFlattenError($reason);
        $this->entityManager->flush();
    }

    /**
     * @param list<string> $renderedPages
     */
    private function replacePages(
        CourseDocument $document,
        array $renderedPages,
    ): void {
        foreach ($document->getPages() as $page) {
            $this->entityManager->remove($page);
        }

        $document->clearPages();
        $this->entityManager->flush();

        $scope = strval($document->getId());

        foreach ($renderedPages as $index => $renderedPage) {
            $dimensions = getimagesize($renderedPage);
            if (false === $dimensions) {
                throw new PdfRasterizerException(sprintf('Rendered page "%s" is not a readable image.', $renderedPage));
            }

            $stored = $this->fileStorage->store(
                StorageNamespace::EducationDocumentPage,
                $renderedPage,
                $scope,
            );

            $page = new CourseDocumentPage();
            $page->setPageNumber($index + 1);
            $page->setPath($stored->path);
            $page->setWidth($dimensions[0]);
            $page->setHeight($dimensions[1]);

            $document->addPage($page);
            $this->entityManager->persist($page);
        }
    }

    /**
     * Copy the stored PDF onto the real filesystem, where poppler can open it.
     */
    private function copyToWorkspace(
        string $storedPath,
        string $workspace,
    ): string {
        $localPath = $workspace . '/source.pdf';

        $source = $this->fileStorage->readStream($storedPath);
        $target = fopen(
            $localPath,
            'wb',
        );

        if (false === $target) {
            throw new PdfRasterizerException(sprintf('Could not open "%s" for writing.', $localPath));
        }

        try {
            stream_copy_to_stream(
                $source,
                $target,
            );
        } finally {
            fclose($target);
        }

        return $localPath;
    }
}
