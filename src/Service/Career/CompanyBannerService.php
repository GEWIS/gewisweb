<?php

declare(strict_types=1);

namespace App\Service\Career;

use App\Entity\Application\Enums\NotificationType;
use App\Entity\Career\CompanyBannerPackage;
use App\Entity\Career\Enums\CompanyAuditVerbs;
use App\Entity\User\CompanyUser;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Message\Application\PublishDomainNotificationMessage;
use App\Service\Application\FileStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Putting an image on a banner package. Both sides of the arrangement come through here: a company proposes a banner
 * and waits for the committee, and the committee sets one straight away because there is nobody left to ask.
 *
 * Whatever the new image displaces is reclaimed once the change is stored, so a proposal that was thought better of,
 * or a banner that has been replaced, does not leave its bytes behind.
 */
final readonly class CompanyBannerService
{
    public function __construct(
        private CompanyImageUploadService $imageUploadService,
        private CompanyAuditLogger $auditLogger,
        private FileStorage $fileStorage,
        private MessageBusInterface $messageBus,
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

    /**
     * A company putting a banner up for the committee to look at. Whatever is on the site stays there in the meantime.
     *
     * @return bool whether the image could be stored
     */
    public function propose(
        CompanyBannerPackage $package,
        UploadedFile $file,
        CompanyUser $proposedBy,
    ): bool {
        $path = $this->store(
            $package,
            $file,
        );
        if (null === $path) {
            return false;
        }

        // Thinking better of a proposal before the committee has looked at it leaves the earlier upload behind, so
        // what is reclaimed here is that one and not the banner that is live.
        $replaced = $package->proposeImage(
            $path,
            $proposedBy,
        );

        $this->settle(
            $package,
            $proposedBy,
            CompanyAuditVerbs::BannerProposed,
            $replaced,
        );

        $id = $package->getId();
        if (null !== $id) {
            $this->messageBus->dispatch(new PublishDomainNotificationMessage(
                NotificationType::CompanyBannerAwaitingReview,
                $id,
                UserRoles::CompanyAdmin,
            ));
        }

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
        CompanyUser|User $actor,
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
