<?php

declare(strict_types=1);

namespace App\Service\Career;

use App\Entity\Application\Enums\ImageProfile;
use App\Entity\Application\Enums\StorageNamespace;
use App\Entity\Career\Company;
use App\Entity\Career\Enums\CompanyBannerFormats;
use App\Message\Photo\ProcessImageVariantsMessage;
use App\Service\Application\FileStorage;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

use function strval;

/**
 * Stores a logo or a banner a company uploaded, scoped to that company, and queues its variants so the sizes the pages
 * ask for exist before anyone asks. Both images go through the same shape: store, queue, and on any failure reclaim
 * whatever this call freshly wrote.
 *
 * The caller decides what the returned path becomes. A logo goes onto a draft revision and only reaches the public
 * page through review; a banner goes into the package's pending slot and waits for the committee there, unless the
 * committee is the one uploading it.
 */
final readonly class CompanyImageUploadService
{
    public function __construct(
        private FileStorage $fileStorage,
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @return string|null the stored path, or null when the file could not be stored
     */
    public function uploadLogo(
        Company $company,
        UploadedFile $file,
    ): ?string {
        return $this->store(
            $company,
            $file,
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
        return $this->store(
            $company,
            $file,
            $format->imageProfile(),
        );
    }

    private function store(
        Company $company,
        UploadedFile $file,
        ImageProfile $profile,
    ): ?string {
        $stored = null;

        try {
            // store() validates the detected MIME type and the size limit, and de-duplicates by content hash. Company
            // images are scoped per company, so the same bytes for another company get their own path.
            $stored = $this->fileStorage->store(
                StorageNamespace::CompanyImage,
                $file->getPathname(),
                strval($company->getId()),
            );

            $this->messageBus->dispatch(new ProcessImageVariantsMessage(
                $stored->path,
                $profile,
            ));

            return $stored->path;
        } catch (Throwable) {
            // Reclaim the bytes this call freshly wrote; a pre-existing (de-duplicated) file is left alone, since
            // something else still points at it.
            if (
                null !== $stored
                && !$stored->deduplicated
            ) {
                $this->fileStorage->remove($stored->path);
            }

            return null;
        }
    }
}
