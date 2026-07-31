<?php

declare(strict_types=1);

namespace App\Tests\Integration\Twig\Components\Notification;

use App\Entity\Application\Enums\NotificationType;
use App\Entity\Application\Notification;
use App\Entity\User\User;
use App\Tests\Integration\DatabaseTestCase;
use App\Twig\Components\Notification\Bell;
use DateTimeImmutable;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use function array_column;

/**
 * The notification centre now shows a mix of what went out to everyone and what was addressed to the member reading
 * it, so these exercise the real component over the real repository after authenticating on the token storage.
 *
 * Album #1 exists in the fixtures, which is what lets a broadcast notification resolve its subject.
 */
final class BellTest extends DatabaseTestCase
{
    public function testANotificationForEveryoneIsShownToEveryone(): void
    {
        $this->broadcast();
        $this->entityManager->flush();

        self::assertCount(
            1,
            $this->bellFor(8025)->getEntries(),
        );
    }

    public function testANotificationAddressedToTheMemberIsShownToThem(): void
    {
        $this->addressed(
            8025,
            'Chrome 124',
        );
        $this->entityManager->flush();

        self::assertSame(
            ['Chrome 124'],
            array_column(
                $this->bellFor(8025)->getEntries(),
                'name',
            ),
        );
    }

    public function testANotificationAddressedToSomeoneElseIsNotShown(): void
    {
        $this->addressed(
            8000,
            'Chrome 124',
        );
        $this->entityManager->flush();

        self::assertSame(
            [],
            $this->bellFor(8025)->getEntries(),
        );
    }

    /**
     * A notification that carries its own label reads by that label rather than by its subject, which is what lets the
     * account warnings that need it outlive whatever they describe.
     */
    public function testTheDeviceIsPreferredOverLookingTheSubjectUp(): void
    {
        $this->addressed(
            8025,
            'Chrome 124',
        );
        $this->entityManager->flush();

        $entries = $this->bellFor(8025)->getEntries();

        self::assertCount(
            1,
            $entries,
        );
        self::assertSame(
            'Chrome 124',
            $entries[0]['name'],
        );
    }

    private function signIn(string $browser): Notification
    {
        $notification = new Notification();
        $notification->setType(NotificationType::SignIn);
        $notification->setContext(['browser' => $browser]);
        $notification->setRecipient(
            $this->member(8025),
            null,
        );
        $notification->setCreatedAt(new DateTimeImmutable());
        $this->entityManager->persist($notification);

        return $notification;
    }

    private function broadcast(): Notification
    {
        $notification = new Notification();
        $notification->setType(NotificationType::AlbumPublished);
        $notification->setSubjectId(1);
        $notification->setCreatedAt(new DateTimeImmutable());
        $this->entityManager->persist($notification);

        return $notification;
    }

    private function addressed(
        int $userIdentifier,
        string $browser,
    ): Notification {
        $notification = $this->signIn($browser);
        $notification->setRecipient(
            $this->member($userIdentifier),
            null,
        );

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

    private function bellFor(int $lidnr): Bell
    {
        $user = $this->entityManager->getRepository(User::class)->find($lidnr);
        self::assertInstanceOf(
            User::class,
            $user,
        );

        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $user,
            'main',
            ['ROLE_USER'],
        ));

        return self::getContainer()->get(Bell::class);
    }
}
