<?php

declare(strict_types=1);

namespace Application\Service;

use DateTime;
use setasign\Fpdi\Tcpdf\Fpdi;
use User\Authentication\Adapter\UserAdapter;
use User\Authentication\AuthenticationService;
use User\Authentication\Storage\UserSession;

use function atan;
use function escapeshellarg;
use function exec;
use function rad2deg;
use function sin;
use function sprintf;
use function sqrt;
use function sys_get_temp_dir;
use function tempnam;

/**
 * @psalm-template TUserAuth of AuthenticationService<UserSession, UserAdapter>
 */
class WatermarkService
{
    // The font size of the watermark
    private const FONT_SIZE_DIAGONAL = 32;
    private const FONT_SIZE_HORIZONTAL = 8;
    private const FONT = 'freesansb';

    /**
     * @psalm-param TUserAuth $authService
     */
    public function __construct(
        private readonly AuthenticationService $authService,
        private readonly string $remoteAddress,
    ) {
    }

    /**
     * @param string $path The CFS path of the file to watermark
     *
     * @return string The CFS path of the watermarked file
     */
    public function watermarkPdf(
        string $path,
        string $fileName,
        bool $scanned = false,
    ): string {
        $pdf = new Fpdi();
        $pdf->setTitle($fileName);
        $pages = $pdf->setSourceFile($path);
        $watermark = $this->getWatermarkText();

        for ($page = 1; $page <= $pages; $page++) {
            // Import a page from the source PDF, this is used to determine all specifications for this specific page,
            // such as the height, width, and orientation.
            $templateIndex = $pdf->importPage($page);
            $templateSpecs = $pdf->getTemplateSize($templateIndex);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Add an actual page to the resulting PDF, otherwise you only get a blank page. Uses the original page as a
            // template.
            $pdf->AddPage($templateSpecs['orientation']);
            $pdf->useTemplate($templateIndex, 0, 0, $templateSpecs['width'], $templateSpecs['height'], true);

            // Do the actual watermarking.
            $pdf->setFont(self::FONT, '', self::FONT_SIZE_DIAGONAL);
            $pdf->setTextColor(212, 0, 0);
            // Set alpha to 50% for the diagonal watermark, the horizontal watermark will have a higher alpha.
            $pdf->setAlpha(0.5);

            // Determine the position of the diagonal watermark, it should be (almost) centred on the page.
            $width = $pdf->getPageWidth();
            $height = $pdf->getPageHeight();
            [$watermarkAngleDeg, $watermarkAngleRad] = $this->calculateTextAngle($width, $height);
            $diagonalWatermarkMaxWidth = 0.8 * sqrt($width * $width + $height * $height);
            $diagonalWatermarkMaxHeight = 20;
            // Adjust the coordinates to account for the shape of the text cell. Note: this is an approximation, the
            // watermark will not be completely centred on the page.
            $diagonalWatermarkAdjustment = $diagonalWatermarkMaxHeight * sin($watermarkAngleRad);
            $x = $diagonalWatermarkAdjustment;
            $y = $height - (2 * $diagonalWatermarkAdjustment);

            // Do the actual transformation. Do not allow overflow to cause page breaks.
            $pdf->setAutoPageBreak(false);
            $pdf->StartTransform();
            $pdf->Rotate($watermarkAngleDeg, $x, $y);
            // Set the current coordinates and use MultiCell to allow long watermarks (e.g., because of long names) to
            // wrap onto a new line. The height of the cell is strictly less than or equal to
            // `diagonalWatermarkMaxHeight`, the font size will be auto-adjusted if there is too much text.
            $pdf->setXY($x, $y);
            $pdf->MultiCell(
                w: $diagonalWatermarkMaxWidth,
                h: $diagonalWatermarkMaxHeight,
                txt: $watermark,
                align: 'C',
                maxh: $diagonalWatermarkMaxHeight,
                valign: 'M',
                fitcell: true,
            );
            $pdf->StopTransform();

            // Set font size for horizontal watermark.
            $pdf->setFontSize(self::FONT_SIZE_HORIZONTAL);
            $pdf->setAlpha(0.75);

            // Determine the position of the horizontal watermark, it should be in the bottom left.
            $horizontalWatermarkMaxWidth = 0.95 * $width;
            $horizontalWatermarkMaxHeight = 20;
            $x = 0.01 * $width;
            $y = 0.92 * $height;

            $pdf->StartTransform();
            $pdf->setXY($x, $y);
            $pdf->MultiCell(
                w: $horizontalWatermarkMaxWidth,
                h: $horizontalWatermarkMaxHeight,
                txt: $watermark,
                align: 'L',
                maxh: $horizontalWatermarkMaxHeight,
                valign: 'B',
                fitcell: true,
            );
            $pdf->StopTransform();
        }

        // Ensure that certain functionality is disabled in the PDF to prevent it from being copied.
        // $pdf->setProtection(self::PDF_DENIED_PERMISSIONS, '', null, 3);

        // Output the file to a temporary location and convert it to a PDF of images.
        $tempName = tempnam(sys_get_temp_dir(), (new DateTime())->format('Y-m-d') . '-');
        $tempFile = $tempName . '.pdf';
        $tempFlatFile = $tempName . '-flat.pdf';
        $pdf->Output($tempFile, 'F');

        $quality = $scanned ? '170' : '120';
        exec(
            'convert -density ' . escapeshellarg($quality) . ' ' . escapeshellarg($tempFile) . ' ' . escapeshellarg(
                $tempFlatFile,
            ),
        );

        return $tempFlatFile;
    }

    /**
     * Calculate the angle at which the watermark text will be displayed.
     *
     * @return array{0: float, 1: float}
     */
    private function calculateTextAngle(
        float|int $width,
        float|int $height,
    ): array {
        $angle = atan($height / $width);

        return [
            rad2deg($angle),
            $angle,
        ];
    }

    /**
     * Uses the identity of the user when signed in or the IP address from which the download is performed.
     *
     * @return string The text containing details on the user who performs the download
     */
    private function getWatermarkText(): string
    {
        $text = 'This exam/summary was downloaded on %s by %s via https://gewis.nl.';
        $dateTime = (new DateTime())->format('Y-m-d H:i:s');

        return sprintf(
            $text,
            $dateTime,
            $this->authService->getIdentity()?->getMember()->getFullName() ?? $this->remoteAddress,
        );
    }
}
