<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository\Application;

use App\Entity\Application\Enums\NotificationType;
use App\Entity\Application\Notification;
use App\Repository\Application\NotificationRepository;
use App\Tests\Integration\DatabaseTestCase;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final class NotificationRepositoryTest extends DatabaseTestCase
{
    public function testFindRecentReturnsTheNewestFirstWithinTheWindow(): void
    {
        $seeded = $this->seed();
        $recent = $this->repository()->findRecent(
            new DateTimeImmutable('-1 week'),
            10,
        );

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
            $this->repository()->findRecent(
                new DateTimeImmutable('-1 week'),
                2,
            ),
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

    private function repository(): NotificationRepository
    {
        return self::getContainer()->get(NotificationRepository::class);
    }
}
