<?php

declare(strict_types=1);

namespace App\Tests\Integration\MessageHandler\Application;

use App\Entity\Application\Enums\NotificationType;
use App\Entity\Application\Notification;
use App\Entity\Career\CompanyBannerPackage;
use App\Entity\User\Enums\UserRoles;
use App\Message\Application\PublishDomainNotificationMessage;
use App\MessageHandler\Application\PublishDomainNotificationHandler;
use App\Tests\Integration\DatabaseTestCase;

/**
 * The handler drops anything whose subject it cannot name, since a subject can be gone by the time an announcement is
 * worked through. A kind whose name is never resolved is therefore never announced at all, which is silent: nothing
 * fails, the notification simply does not appear.
 */
final class PublishDomainNotificationHandlerTest extends DatabaseTestCase
{
    public function testAProposedBannerIsAnnouncedToTheCommittee(): void
    {
        $package = $this->bannerPackage();
        $subjectId = $package->getId();
        self::assertNotNull($subjectId);

        $this->handler()(new PublishDomainNotificationMessage(
            NotificationType::CompanyBannerAwaitingReview,
            $subjectId,
            UserRoles::CompanyAdmin,
        ));

        self::assertNotNull($this->entityManager->getRepository(Notification::class)->findOneBy([
            'type' => NotificationType::CompanyBannerAwaitingReview,
            'subjectId' => $subjectId,
        ]));
    }

    public function testASubjectThatIsGoneIsNotAnnounced(): void
    {
        $this->handler()(new PublishDomainNotificationMessage(
            NotificationType::CompanyBannerAwaitingReview,
            987654,
            UserRoles::CompanyAdmin,
        ));

        self::assertNull($this->entityManager->getRepository(Notification::class)->findOneBy([
            'type' => NotificationType::CompanyBannerAwaitingReview,
            'subjectId' => 987654,
        ]));
    }

    private function bannerPackage(): CompanyBannerPackage
    {
        $package = $this->entityManager->getRepository(CompanyBannerPackage::class)->findOneBy([]);
        self::assertInstanceOf(
            CompanyBannerPackage::class,
            $package,
            'The seed is expected to contain a company banner package.',
        );

        return $package;
    }

    private function handler(): PublishDomainNotificationHandler
    {
        return self::getContainer()->get(PublishDomainNotificationHandler::class);
    }
}
