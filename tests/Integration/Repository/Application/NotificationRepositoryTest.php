<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository\Application;

use App\Entity\Application\Enums\NotificationType;
use App\Entity\Application\Notification;
use App\Entity\User\User;
use App\Repository\Application\NotificationRepository;
use App\Tests\Integration\DatabaseTestCase;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

use function array_map;

final class NotificationRepositoryTest extends DatabaseTestCase
{
    public function testFindRecentReturnsTheNewestFirstWithinTheWindow(): void
    {
        $seeded = $this->seed();
        $recent = $this->findFor(8025);

        // The ancient notification is outside the window; the rest come back newest-first.
        self::assertCount(
            3,
            $recent,
        );
        self::assertSame(
            $seeded['new']->getId(),
            $recent[0]->getId(),
        );
        self::assertSame(
            $seeded['mid']->getId(),
            $recent[1]->getId(),
        );
        self::assertSame(
            $seeded['old']->getId(),
            $recent[2]->getId(),
        );
    }

    public function testFindRecentRespectsTheLimit(): void
    {
        $this->seed();

        self::assertCount(
            2,
            $this->repository()->findRecentFor(
                new DateTimeImmutable('-1 week'),
                $this->member(8025),
                2,
            ),
        );
    }

    public function testANotificationAddressedToSomeoneReachesOnlyThem(): void
    {
        $mine = $this->addressed(8025);
        $theirs = $this->addressed(8000);
        $this->entityManager->flush();

        self::assertSame(
            [$mine->getId()],
            $this->ids($this->findFor(8025)),
        );
        self::assertSame(
            [$theirs->getId()],
            $this->ids($this->findFor(8000)),
        );
    }

    public function testNotificationsAddressedToNobodyReachEveryone(): void
    {
        $this->seed();

        self::assertCount(
            3,
            $this->findFor(8025),
        );
    }

    public function testTheSameSubjectCannotBeAnnouncedTwice(): void
    {
        $this->make(
            99,
            new DateTimeImmutable(),
        );
        $this->entityManager->flush();

        $this->make(
            99,
            new DateTimeImmutable(),
        );

        $this->expectException(UniqueConstraintViolationException::class);
        $this->entityManager->flush();
    }

    /**
     * Notifications that are about nothing in particular are not subject to that constraint, because the database
     * treats nulls as distinct from one another.
     */
    public function testNotificationsWithoutASubjectDoNotCollide(): void
    {
        for ($i = 0; $i < 2; ++$i) {
            $notification = new Notification();
            $notification->setType(NotificationType::AlbumPublished);
            $notification->setCreatedAt(new DateTimeImmutable());
            $this->entityManager->persist($notification);
        }

        $this->entityManager->flush();

        self::assertCount(
            2,
            $this->repository()->findBy(['subjectId' => null]),
        );
    }

    /**
     * @return array{ancient: Notification, old: Notification, mid: Notification, new: Notification}
     */
    private function seed(): array
    {
        $seeded = [
            'ancient' => $this->make(
                1,
                new DateTimeImmutable('-10 days'),
            ),
            'old' => $this->make(
                2,
                new DateTimeImmutable('-3 days'),
            ),
            'mid' => $this->make(
                3,
                new DateTimeImmutable('-2 days'),
            ),
            'new' => $this->make(
                4,
                new DateTimeImmutable('-1 day'),
            ),
        ];

        $this->entityManager->flush();

        return $seeded;
    }

    /**
     * @return Notification[]
     */
    private function findFor(int $lidnr): array
    {
        return $this->repository()->findRecentFor(
            new DateTimeImmutable('-1 week'),
            $this->member($lidnr),
            10,
        );
    }

    /**
     * @param Notification[] $notifications
     *
     * @return array<int, ?int>
     */
    private function ids(array $notifications): array
    {
        return array_map(
            static fn (Notification $notification): ?int => $notification->getId(),
            $notifications,
        );
    }

    private function addressed(int $lidnr): Notification
    {
        $notification = new Notification();
        $notification->setType(NotificationType::AlbumPublished);
        $notification->setContext(['browser' => 'Chrome 124']);
        $notification->setRecipient(
            $this->member($lidnr),
            null,
        );
        $notification->setCreatedAt(new DateTimeImmutable());
        $this->entityManager->persist($notification);

        return $notification;
    }

    private function make(
        int $subjectId,
        DateTimeImmutable $createdAt,
    ): Notification {
        $notification = new Notification();
        $notification->setType(NotificationType::AlbumPublished);
        $notification->setSubjectId($subjectId);
        $notification->setCreatedAt($createdAt);
        $this->entityManager->persist($notification);

        return $notification;
    }

    private function member(int $lidnr): User
    {
        $user = $this->entityManager->getRepository(User::class)->find($lidnr);
        self::assertInstanceOf(
            User::class,
            $user,
        );

        return $user;
    }

    private function repository(): NotificationRepository
    {
        return self::getContainer()->get(NotificationRepository::class);
    }
}
