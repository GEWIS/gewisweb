<?php

declare(strict_types=1);

namespace App\Service\Career;

use App\Entity\Application\Enums\ImageProfile;
use App\Entity\Application\Enums\StorageNamespace;
use App\Entity\Career\Company;
use App\Entity\Career\Enums\CompanyBannerFormats;
use App\Service\Application\AbstractImageUploadService;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use function strval;

/**
 * Stores a logo or a banner a company uploaded, scoped to that company, and queues its variants so the sizes the pages
 * ask for exist before anyone asks.
 *
 * The caller decides what the returned path becomes. A logo goes onto a draft revision and only reaches the public
 * page through review; a banner goes into the package's pending slot and waits for the committee there, unless the
 * committee is the one uploading it.
 */
final readonly class CompanyImageUploadService extends AbstractImageUploadService
{
    /**
     * @return string|null the stored path, or null when the file could not be stored
     */
    public function uploadLogo(
        Company $company,
        UploadedFile $file,
    ): ?string {
        return $this->storeAndQueue(
            StorageNamespace::CompanyImage,
            $file->getPathname(),
            strval($company->getId()),
            ImageProfile::CompanyLogo,
        );
    }

    /**
     * @return string|null the stored path, or null when the file could not be stored
     */
    public function uploadBanner(
        Company $company,
        UploadedFile $file,
        CompanyBannerFormats $format,
    ): ?string {
        return $this->storeAndQueue(
            StorageNamespace::CompanyImage,
            $file->getPathname(),
            strval($company->getId()),
            $format->imageProfile(),
        );
    }
}
