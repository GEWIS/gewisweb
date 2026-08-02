<?php

declare(strict_types=1);

namespace App\Service\Career;

use App\Entity\Career\CompanyBannerPackage;
use App\Entity\Career\Enums\CompanyAuditVerbs;
use App\Entity\User\User;
use App\Service\Application\FileStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Putting an image on a banner package.
 *
 * Whatever the new image displaces is reclaimed once the change is stored, so a banner that has been replaced does not
 * leave its bytes behind.
 */
final readonly class CompanyBannerService
{
    public function __construct(
        private CompanyImageUploadService $imageUploadService,
        private CompanyAuditLogger $auditLogger,
        private FileStorage $fileStorage,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * The committee putting a banner on the site, which is where it stays until somebody replaces it.
     *
     * @return bool whether the image could be stored
     */
    public function publish(
        CompanyBannerPackage $package,
        UploadedFile $file,
        User $publishedBy,
    ): bool {
        $path = $this->store(
            $package,
            $file,
        );
        if (null === $path) {
            return false;
        }

        $replaced = $package->getImage();
        $package->setImage($path);

        $this->settle(
            $package,
            $publishedBy,
            CompanyAuditVerbs::BannerReplaced,
            $replaced,
        );

        return true;
    }

    private function store(
        CompanyBannerPackage $package,
        UploadedFile $file,
    ): ?string {
        return $this->imageUploadService->uploadBanner(
            $package->getCompany(),
            $file,
            $package->getFormat(),
        );
    }

    private function settle(
        CompanyBannerPackage $package,
        User $actor,
        CompanyAuditVerbs $verb,
        ?string $replaced,
    ): void {
        $this->auditLogger->log(
            $package->getCompany(),
            $actor,
            $verb,
        );
        $this->entityManager->flush();

        if (null === $replaced) {
            return;
        }

        $this->fileStorage->remove($replaced);
    }
}
