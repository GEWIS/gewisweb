<?php

namespace Application\Service;

use DateTime;
use setasign\Fpdi\Tcpdf\Fpdi;
use User\Authentication\AuthenticationService;

class WatermarkService
{
    // The font size of the watermark
    private const FONT_SIZE = 32;
    private const FONT = 'freesansb';
    private const PDF_DENIED_PERMISSIONS = [
        'modify',
        'copy',
        'annot-forms',
        'fill-forms',
        'extract',
        'assemble',
        'print-high',
    ];

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
            $pdf->setFont(self::FONT, '', self::FONT_SIZE);
            $pdf->setTextColor(212, 0, 0);
            // We do not have to reset the alpha layer after watermarking, as we are not adding any additional content.
            $pdf->setAlpha(0.5);

            // Determine the position of the watermark, it should be (almost) centred on the page.
            $width = $pdf->getPageWidth();
            $height = $pdf->getPageHeight();
            [$watermarkAngleDeg, $watermarkAngleRad] = $this->calculateTextAngle($width, $height);
            $watermarkMaxWidth = 0.8 * sqrt($width * $width + $height * $height);
            $watermarkMaxHeight = 20;
            // Adjust the coordinates to account for the shape of the text cell. Note: this is an approximation, the
            // watermark will not be completely centred on the page.
            $watermarkAdjustment = $watermarkMaxHeight * sin($watermarkAngleRad);
            $x = $watermarkAdjustment;
            $y = $height - (2 * $watermarkAdjustment);

            // Do the actual transformation. Do not allow overflow to cause page breaks.
            $pdf->setAutoPageBreak(false);
            $pdf->StartTransform();
            $pdf->Rotate($watermarkAngleDeg, $x, $y);
            // Set the current coordinates and use MultiCell to allow long watermarks (e.g., because of long names) to
            // wrap onto a new line. The height of the cell is strictly less than or equal to `watermarkMaxHeight`, the
            // font size will be auto-adjusted if there is too much text.
            $pdf->setXY($x, $y);
            $pdf->MultiCell(
                w: $watermarkMaxWidth,
                h: $watermarkMaxHeight,
                txt: $watermark,
                align: 'C',
                maxh: $watermarkMaxHeight,
                valign: 'C',
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
        exec("convert -density 120 " . escapeshellarg($tempFile) . " " . escapeshellarg($tempFlatFile));

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
