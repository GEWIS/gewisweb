<?php

namespace Application\Service;

use Imagick;
use ImagickDraw;
use ImagickException;
use Laminas\Mvc\I18n\Translator;
use User\Authentication\AuthenticationService;

class WatermarkService
{
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
     */
    public function watermarkPdf(string $path): string
    {
        $pdf = new Imagick($path);
        $watermarkText = $this->getWatermarkText();
        $drawSettings = new ImagickDraw();
        $pdf->annotateImage($drawSettings, 0, 0, 45, $watermarkText);
        $newPath = tempnam($this->storageConfig['storage_temp_dir'], 'watermark');
        $pdf->writeImage($newPath);
        return $newPath;
    }

    private function getWatermarkText()
    {
        $date = (new \DateTime())->format('Y-m-d H-i-s');
        $user = $this->authService->getIdentity();
        if ($user !== null) {
            return sprintf(
                "This pdf was downloaded on %s by %s from https://gewis.nl",
                $date,
                $user->getMember()->getFullName()
            );
        }
        return sprintf(
            "This pdf was downloaded on %s from %s from https://gewis.nl",
            $date,
            $this->remoteAddress
        );
    }
}
