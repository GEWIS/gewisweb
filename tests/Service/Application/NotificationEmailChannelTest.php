<?php

declare(strict_types=1);

namespace App\Tests\Service\Application;

use App\Entity\Application\Enums\NotificationType;
use App\Entity\Application\Notification;
use App\Entity\Decision\Member;
use App\Entity\User\PendingNotificationEmail;
use App\Entity\User\User;
use App\Repository\User\NotificationEmailSubscriptionRepository;
use App\Service\Application\NotificationEmailChannel;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class NotificationEmailChannelTest extends TestCase
{
    public function testASubscriberWithAnEmailIsQueued(): void
    {
        $subscriptions = self::createStub(NotificationEmailSubscriptionRepository::class);
        $subscriptions->method('findSubscribedUsers')->willReturn([$this->subscriber('ada@example.com')]);

        $entityManager = self::createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(PendingNotificationEmail::class));
        $entityManager->expects(self::once())->method('flush');

        new NotificationEmailChannel($subscriptions, $entityManager)->deliver($this->notification());
    }

    public function testNothingIsQueuedWithoutSubscribers(): void
    {
        $subscriptions = self::createStub(NotificationEmailSubscriptionRepository::class);
        $subscriptions->method('findSubscribedUsers')->willReturn([]);

        $entityManager = self::createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');
        $entityManager->expects(self::never())->method('flush');

        new NotificationEmailChannel($subscriptions, $entityManager)->deliver($this->notification());
    }

    private function subscriber(string $email): User
    {
        $member = self::createStub(Member::class);
        $member->method('getEmail')->willReturn($email);
        $member->method('getDeleted')->willReturn(false);
        $member->method('getHidden')->willReturn(false);
        $member->method('isExpired')->willReturn(false);

        $user = self::createStub(User::class);
        $user->method('getMember')->willReturn($member);

        return $user;
    }

    private function notification(): Notification
    {
        $notification = new Notification();
        $notification->setType(NotificationType::AlbumPublished);
        $notification->setSubjectId(1);

        return $notification;
    }
}
