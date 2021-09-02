<?php

namespace Application\Service;

use DateTime;
use Howtomakeaturn\PDFInfo\PDFInfo;
use Imagick;
use ImagickDraw;
use ImagickDrawException;
use ImagickException;
use ImagickPixel;
use User\Authentication\AuthenticationService;

class WatermarkService
{
    // The font size of the watermark
    private const FONT_SIZE = 48;

    // The quality of the produced PDFs
    private const DPI = 150;

    private array $storageConfig;
    private AuthenticationService $authService;
    private string $remoteAddress;

    public function __construct(array $storageConfig, AuthenticationService $authService, string $remoteAddress)
    {
        $this->storageConfig = $storageConfig;
        $this->authService = $authService;
        $this->remoteAddress = $remoteAddress;
    }

    /**
     * @param string $path The CFS path of the file to watermark
     * @return string The CFS path of the watermarked file
     * @throws ImagickException
     * @throws ImagickDrawException
     */
    public function watermarkPdf(string $path): string
    {
        $watermarkText = $this->getWatermarkText();
        $newPath = tempnam($this->storageConfig['watermark_dir'], (new DateTime())->format('Y-m-d') . '-');
        $newPath = $newPath . '.pdf';

        $fillPixelLight = new ImagickPixel('rgb(200, 200, 200)');
        $fillPixelDark = new ImagickPixel('rgb(50, 50, 50)');

        $drawSettings = new ImagickDraw();
        $drawSettings->setFontSize(self::FONT_SIZE);
        $drawSettings->setTextAlignment(Imagick::ALIGN_CENTER);
        $drawSettings->setFillColor($fillPixelLight);
        $drawSettings->setFillOpacity(0.20);
        $drawSettings->setStrokeWidth(1);
        $drawSettings->setStrokeColor($fillPixelDark);
        $drawSettings->setStrokeOpacity(0.20);

        $pdf = new Imagick();
        $pages = (new Imagick($path))->getNumberImages();
        for ($page = 0; $page < $pages; $page++) {
            $pdfPage = new Imagick();
            $pdfPage->setResolution(self::DPI, self::DPI);
            $pdfPage->readImage($path . '[' . $page . ']');

            $sizes = $pdfPage->getImagePage();
            $sizeX = $sizes['width'];
            $sizeY = $sizes['height'];

            $pdfPage->annotateImage($drawSettings, $sizeX / 2, $sizeY / 2, 55, $watermarkText);
            $pdfPage->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);

            $pdf->addImage($pdfPage);
        }

        $pdf->setCompression(Imagick::COMPRESSION_ZIP);

        $pdf->writeImages($newPath, true);

        return $newPath;
    }

    /**
     * Uses the identity of the user when signed in or the IP address from which the download is performed.
     *
     * @return string The text containing details on the user who performs the download
     */
    private function getWatermarkText(): string
    {
        $dateTime = (new DateTime())->format('Y-m-d H-i-s');
        $user = $this->authService->getIdentity();

        if ($user !== null) {
            return sprintf(
                "This pdf was downloaded on %s by %s from https://gewis.nl",
                $dateTime,
                $user->getMember()->getFullName()
            );
        }

        return sprintf(
            "This pdf was downloaded on %s from %s from https://gewis.nl",
            $dateTime,
            $this->remoteAddress
        );
    }
}
