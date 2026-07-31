<?php

declare(strict_types=1);

namespace App\Tests\Integration\Twig\Components\Notification;

use App\Entity\Application\Enums\NotificationType;
use App\Entity\Application\Notification;
use App\Entity\User\User;
use App\Tests\Integration\DatabaseTestCase;
use App\Twig\Components\Notification\Bell;
use DateTimeImmutable;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use function array_column;
use function implode;

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

    /**
     * Ten lines each saying an account was signed in is a worse answer to "what happened" than one saying it was
     * signed in ten times.
     */
    public function testARunOfOneKindIsShownAsOneLine(): void
    {
        $this->signIn('Chrome 124');
        $this->signIn('Firefox 153');
        $this->signIn('Safari 18');
        $this->entityManager->flush();

        $entries = $this->bellFor(8025)->getEntries();

        self::assertCount(
            1,
            $entries,
        );
        self::assertCount(
            3,
            $entries[0]['ids'],
        );
    }

    /**
     * The badge counts notifications, not lines: a line standing for three unread ones is three.
     */
    public function testTheBadgeCountsWhatIsBehindTheLines(): void
    {
        $this->signIn('Chrome 124');
        $this->signIn('Firefox 153');
        $this->entityManager->flush();

        self::assertSame(
            2,
            $this->bellFor(8025)->getUnreadCount(),
        );
    }

    public function testActingOnALineCoversEverythingBehindIt(): void
    {
        $this->signIn('Chrome 124');
        $this->signIn('Firefox 153');
        $this->entityManager->flush();

        $bell = $this->bellFor(8025);
        $bell->markRead(implode(
            ',',
            $bell->getEntries()[0]['ids'],
        ));

        self::assertSame(
            0,
            $bell->getUnreadCount(),
        );
    }

    /**
     * No single one of them is what the reader is after, so a line standing for several points at the list.
     */
    public function testALineStandingForSeveralPointsAtTheList(): void
    {
        $this->signIn('Chrome 124');
        $this->signIn('Firefox 153');
        $this->entityManager->flush();

        self::assertStringContainsString(
            'security',
            $this->bellFor(8025)->getEntries()[0]['href'],
        );
    }

    /**
     * A sign-in waiting on its second factor already carries the member on the token, so the centre has to ask whether
     * that sign-in finished. Otherwise the password on its own is enough to read what the account has been told.
     */
    public function testASignInWaitingOnItsSecondFactorIsShownNothing(): void
    {
        $this->signIn('Chrome 124');
        $this->entityManager->flush();

        self::assertSame(
            [],
            $this->bellMidTwoFactorFor(8025)->getEntries(),
        );
    }

    /**
     * Nor can it clear away what it is not allowed to read.
     */
    public function testASignInWaitingOnItsSecondFactorCannotMarkAnythingRead(): void
    {
        $this->signIn('Chrome 124');
        $this->entityManager->flush();

        $this->bellMidTwoFactorFor(8025)->markAllRead();

        self::assertNull($this->member(8025)->getSettings()?->getNotificationsReadAt());
    }

    /**
     * Different kinds next to each other stay apart, however close together they arrived.
     */
    public function testDifferentKindsAreNotFoldedTogether(): void
    {
        $this->signIn('Chrome 124');
        $this->broadcast();
        $this->entityManager->flush();

        self::assertCount(
            2,
            $this->bellFor(8025)->getEntries(),
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
        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $this->member($lidnr),
            'main',
            ['ROLE_USER'],
        ));

        return self::getContainer()->get(Bell::class);
    }

    /**
     * The same bell, read by a sign-in that has cleared its password but not yet its second factor.
     */
    private function bellMidTwoFactorFor(int $lidnr): Bell
    {
        self::getContainer()->get('security.token_storage')->setToken(new TwoFactorToken(
            new UsernamePasswordToken(
                $this->member($lidnr),
                'main',
                ['ROLE_USER'],
            ),
            null,
            'main',
            ['totp'],
        ));

        return self::getContainer()->get(Bell::class);
    }
}
