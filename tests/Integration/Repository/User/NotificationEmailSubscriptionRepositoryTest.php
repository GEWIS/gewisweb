<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository\User;

use App\Entity\Application\Enums\NotificationEmailFrequency;
use App\Entity\Application\Enums\NotificationType;
use App\Entity\User\User;
use App\Repository\User\NotificationEmailSubscriptionRepository;
use App\Tests\Integration\DatabaseTestCase;

final class NotificationEmailSubscriptionRepositoryTest extends DatabaseTestCase
{
    public function testOptInsAreAddedUpdatedRemovedAndListed(): void
    {
        $repository = $this->repository();
        $user = $this->user();

        self::assertSame(
            [],
            $repository->findForUser($user),
        );

        $repository->setForUser(
            $user,
            [NotificationType::AlbumPublished->value => NotificationEmailFrequency::Weekly],
        );
        $this->entityManager->flush();

        $subscriptions = $repository->findForUser($user);
        self::assertCount(
            1,
            $subscriptions,
        );
        self::assertSame(
            NotificationType::AlbumPublished,
            $subscriptions[0]->getCategory(),
        );
        self::assertSame(
            NotificationEmailFrequency::Weekly,
            $subscriptions[0]->getFrequency(),
        );

        // Re-saving the same category with a different frequency updates the existing row instead of duplicating it.
        $repository->setForUser(
            $user,
            [NotificationType::AlbumPublished->value => NotificationEmailFrequency::Daily],
        );
        $this->entityManager->flush();

        $subscriptions = $repository->findForUser($user);
        self::assertCount(
            1,
            $subscriptions,
        );
        self::assertSame(
            NotificationEmailFrequency::Daily,
            $subscriptions[0]->getFrequency(),
        );

        // Switching the selection removes the old opt-in and adds the new one.
        $repository->setForUser(
            $user,
            [NotificationType::ActivityPublished->value => NotificationEmailFrequency::Immediately],
        );
        $this->entityManager->flush();

        $subscriptions = $repository->findForUser($user);
        self::assertCount(
            1,
            $subscriptions,
        );
        self::assertSame(
            NotificationType::ActivityPublished,
            $subscriptions[0]->getCategory(),
        );
    }

    public function testFindSubscribedUsersReturnsOnlyThoseWhoOptedIn(): void
    {
        $repository = $this->repository();
        $user = $this->user();

        $repository->setForUser(
            $user,
            [NotificationType::ActivityPublished->value => NotificationEmailFrequency::Immediately],
        );
        $this->entityManager->flush();

        self::assertContains(
            $user,
            $repository->findSubscribedUsers(NotificationType::ActivityPublished),
        );
        self::assertSame(
            [],
            $repository->findSubscribedUsers(NotificationType::AlbumPublished),
        );
    }

    private function repository(): NotificationEmailSubscriptionRepository
    {
        return self::getContainer()->get(NotificationEmailSubscriptionRepository::class);
    }

    private function user(): User
    {
        $user = $this->entityManager->getRepository(User::class)->find(8025);
        self::assertInstanceOf(
            User::class,
            $user,
        );

        return $user;
    }
}
