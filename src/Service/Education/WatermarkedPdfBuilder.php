<?php

declare(strict_types=1);

namespace App\Service\Education;

use App\Entity\Education\CourseDocument;
use App\Entity\Education\CourseDocumentDownload;
use App\Entity\Education\CourseDocumentPage;
use App\Service\Application\FileStorage;
use FPDF;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

use function bin2hex;
use function fclose;
use function fopen;
use function random_bytes;
use function sprintf;
use function stream_copy_to_stream;
use function sys_get_temp_dir;

/**
 * Rebuilds a course document as a watermarked PDF.
 *
 * Every page is an image, so nothing from the original is selectable, searchable or extractable. The only text objects
 * in the result are the tag written on the first page and the same string in the document metadata: it names the site
 * the file came from and the download it was built for, which is what an external platform's detector looks for. The
 * tag is drawn in white at the very top of the page, where a watermarked scan has no content, so `pdftotext` reads it
 * without it being obtrusive on screen or in print.
 */
final readonly class WatermarkedPdfBuilder
{
    /** Points per inch, the unit FPDF pages are laid out in. */
    private const int POINTS_PER_INCH = 72;

    private const int JPEG_QUALITY = 80;

    private const string TAG_FONT = 'Times';
    private const int TAG_FONT_SIZE = 6;

    /** How far the tag sits from the top-left corner, in points. */
    private const int TAG_MARGIN = 3;

    public function __construct(
        private FileStorage $fileStorage,
        private WatermarkRenderer $watermarkRenderer,
        private WatermarkTextBuilder $watermarkTextBuilder,
        private Filesystem $filesystem,
        private string $watermarkTag,
    ) {
    }

    /**
     * @throws RuntimeException if the document has no rendered pages to rebuild from.
     */
    public function build(CourseDocumentDownload $download): string
    {
        $document = $download->getDocument();
        $pages = $document->getPages();

        if ($pages->isEmpty()) {
            throw new RuntimeException(sprintf(
                'Course document %d has no rendered pages to rebuild from.',
                $document->getId() ?? 0,
            ));
        }

        $workspace = sprintf(
            '%s/course-download-%s',
            sys_get_temp_dir(),
            bin2hex(random_bytes(8)),
        );
        $this->filesystem->mkdir($workspace);

        try {
            return $this->assemble(
                $download,
                $document,
                $workspace,
            );
        } finally {
            $this->filesystem->remove($workspace);
        }
    }

    private function assemble(
        CourseDocumentDownload $download,
        CourseDocument $document,
        string $workspace,
    ): string {
        $text = $this->watermarkTextBuilder->forDownload($download);
        $dpi = $document->getScanned()
            ? PdfRasterizer::DPI_SCANNED
            : PdfRasterizer::DPI_DIGITAL;

        // Points, because that is what a page size derived from pixels and a resolution comes out in. FPDF measures in
        // millimetres unless told otherwise, which would silently make every page nearly three times too large.
        $pdf = new FPDF(
            'P',
            'pt',
        );
        $pdf->SetAutoPageBreak(false);
        $pdf->SetCreator('GEWIS');
        $pdf->SetKeywords($this->tagFor($download));

        foreach ($document->getPages() as $page) {
            $stamped = $this->stampPage(
                $page,
                $text,
                $workspace,
            );

            // Lay the page out at the size it was rendered at, so the rebuilt document keeps the original's proportions
            // whatever the rasterization resolution was.
            $width = $page->getWidth() / $dpi * self::POINTS_PER_INCH;
            $height = $page->getHeight() / $dpi * self::POINTS_PER_INCH;

            $pdf->AddPage(
                $page->isPortrait() ? 'P' : 'L',
                [
                    $width,
                    $height,
                ],
            );
            $pdf->Image(
                $stamped,
                0,
                0,
                $width,
                $height,
                'JPG',
            );

            if (1 !== $page->getPageNumber()) {
                continue;
            }

            $pdf->SetFont(
                self::TAG_FONT,
                '',
                self::TAG_FONT_SIZE,
            );
            $pdf->SetTextColor(
                255,
                255,
                255,
            );
            $pdf->Text(
                self::TAG_MARGIN,
                self::TAG_FONT_SIZE + self::TAG_MARGIN,
                $this->tagFor($download),
            );
        }

        return $pdf->Output('S');
    }

    private function stampPage(
        CourseDocumentPage $page,
        string $text,
        string $workspace,
    ): string {
        $sourcePath = sprintf(
            '%s/page-%d-source.jpg',
            $workspace,
            $page->getPageNumber(),
        );
        $stampedPath = sprintf(
            '%s/page-%d.jpg',
            $workspace,
            $page->getPageNumber(),
        );

        $this->copyToWorkspace(
            $page->getPath(),
            $sourcePath,
        );

        $this->watermarkRenderer->stamp(
            $sourcePath,
            $text,
            $stampedPath,
            self::JPEG_QUALITY,
        );

        return $stampedPath;
    }

    /**
     * The machine-readable marker written into the delivered file: the configured site tag plus the reference of this
     * download, so a copy found elsewhere identifies both where it came from and which request produced it.
     */
    private function tagFor(CourseDocumentDownload $download): string
    {
        return sprintf(
            '%s %s',
            $this->watermarkTag,
            $download->getReference(),
        );
    }

    private function copyToWorkspace(
        string $storedPath,
        string $localPath,
    ): void {
        $source = $this->fileStorage->readStream($storedPath);
        $target = fopen(
            $localPath,
            'wb',
        );

        if (false === $target) {
            throw new RuntimeException(sprintf('Could not open "%s" for writing.', $localPath));
        }

        try {
            stream_copy_to_stream(
                $source,
                $target,
            );
        } finally {
            fclose($target);
        }
    }
}
