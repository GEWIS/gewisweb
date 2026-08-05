<?php

declare(strict_types=1);

namespace App\Service\Education;

use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

use function glob;
use function preg_match;
use function sort;
use function sprintf;

use const GLOB_NOSORT;

/**
 * Renders a PDF to one JPEG per page with poppler's `pdftoppm`.
 *
 * Course documents are never served as the PDF that was uploaded: they are rasterized once, and every download is
 * rebuilt from those page images so no text from the original survives into the delivered file. Rasterizing is the
 * expensive half of that, which is why it runs once per document in a worker rather than per download.
 *
 * `pdftoppm` renders a whole document in a single process at a flat memory cost (poppler releases each page before
 * starting the next), so a long document costs proportionally more time but no more memory. Measured on a 70-page
 * document at 150 dpi: 6.0 s and 28 MB resident, against 5.6 s and 45 MB for `mutool draw`, which additionally cannot
 * write JPEG in Debian and would need every page re-encoded afterwards.
 */
final readonly class PdfRasterizer
{
    /** Born-digital documents are legible at 150 dpi; scans need more because they are already raster. */
    public const int DPI_DIGITAL = 150;
    public const int DPI_SCANNED = 200;

    private const int JPEG_QUALITY = 80;
    private const int INFO_TIMEOUT = 30;
    private const int RENDER_TIMEOUT = 600;

    /**
     * @throws PdfRasterizerException if the file is not a PDF poppler can read.
     */
    public function pageCount(string $pdfPath): int
    {
        $process = new Process(['pdfinfo', $pdfPath]);
        $process->setTimeout(self::INFO_TIMEOUT);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new PdfRasterizerException(
                sprintf(
                    'Could not read "%s": %s',
                    $pdfPath,
                    $process->getErrorOutput(),
                ),
            );
        }

        if (
            1 !== preg_match(
                '/^Pages:\s+(\d+)$/m',
                $process->getOutput(),
                $matches,
            )
        ) {
            throw new PdfRasterizerException(sprintf('Could not determine the page count of "%s".', $pdfPath));
        }

        return (int) $matches[1];
    }

    /**
     * $outputDirectory is written to but never created or cleaned up here; the caller owns its lifetime.
     *
     * @return list<string>
     *
     * @throws PdfRasterizerException if rendering fails or produces nothing.
     */
    public function rasterize(
        string $pdfPath,
        string $outputDirectory,
        int $dpi,
    ): array {
        $process = new Process([
            'pdftoppm',
            '-jpeg',
            '-jpegopt',
            'quality=' . self::JPEG_QUALITY,
            '-r',
            (string) $dpi,
            $pdfPath,
            $outputDirectory . '/page',
        ]);
        $process->setTimeout(self::RENDER_TIMEOUT);

        try {
            $process->run();
        } catch (ProcessTimedOutException $e) {
            throw new PdfRasterizerException(
                sprintf(
                    'Rendering "%s" timed out.',
                    $pdfPath,
                ),
                previous: $e,
            );
        }

        if (!$process->isSuccessful()) {
            throw new PdfRasterizerException(
                sprintf(
                    'Could not render "%s": %s',
                    $pdfPath,
                    $process->getErrorOutput(),
                ),
            );
        }

        // pdftoppm zero-pads the page number to the width of the page count, so the padding is uniform within one
        // document and sorting the names lexically puts them in page order.
        $pages = glob(
            $outputDirectory . '/page-*.jpg',
            GLOB_NOSORT,
        );

        if (
            false === $pages
            || [] === $pages
        ) {
            throw new PdfRasterizerException(sprintf('Rendering "%s" produced no pages.', $pdfPath));
        }

        sort($pages);

        return $pages;
    }
}
