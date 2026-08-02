<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler\User;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Application\Enums\NotificationType;
use App\Entity\Application\Notification;
use App\Entity\Career\Company;
use App\Entity\Decision\Member;
use App\Entity\User\CompanyUser;
use App\Entity\User\User;
use App\Message\User\SecurityNotificationMessage;
use App\MessageHandler\User\SecurityNotificationHandler;
use App\Repository\User\CompanyUserRepository;
use App\Repository\User\UserRepository;
use App\Service\Application\DeviceDescription;
use App\Service\Application\NotificationContextResolver;
use App\Service\Application\NotificationPublisher;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_fill;
use function array_map;
use function count;
use function strtr;

final class SecurityNotificationHandlerTest extends TestCase
{
    private const array ORIGIN = [
        'browser' => 'Chrome 124',
        'system' => 'Windows 11',
        'address' => '192.0.2.1',
    ];
    private const string DEVICE = 'Chrome 124 on Windows 11 (192.0.2.1)';

    /** @var list<Notification> */
    private array $published = [];

    /** @var list<TemplatedEmail> */
    private array $sent = [];

    public function testTheNoticeIsAddressedToTheMemberAndSaysWhereItHappened(): void
    {
        $this->handler()($this->message());

        self::assertCount(
            1,
            $this->published,
        );
        $notification = $this->published[0];
        self::assertSame(
            NotificationType::SignIn,
            $notification->getType(),
        );
        self::assertSame(
            AlertTypes::Warning,
            $notification->getLevel(),
        );
        self::assertNotNull($notification->getRecipientUser());
        self::assertNull($notification->getRecipientCompanyUser());
        self::assertSame(
            self::ORIGIN,
            $notification->getContext(),
        );
    }

    public function testTheMemberIsEmailedAtTheirOwnAddress(): void
    {
        $this->handler()($this->message());

        self::assertSame(
            'ada@example.com',
            $this->sent[0]->getTo()[0]->getAddress(),
        );
        self::assertSame(
            'New sign-in to your GEWIS account',
            $this->sent[0]->getSubject(),
        );
        self::assertSame(
            'https://gewis.nl/user_forgot_password',
            $this->sent[0]->getContext()['resetUrl'],
        );
    }

    public function testACompanyIsEmailedAtItsRepresentativeAndLinkedToItsOwnPage(): void
    {
        $this->handler()($this->message(
            firewallName: 'company',
            userIdentifier: 'rep@example.com',
        ));

        self::assertSame(
            'rep@example.com',
            $this->sent[0]->getTo()[0]->getAddress(),
        );
        self::assertSame(
            'https://gewis.nl/company_user_forgot_password',
            $this->sent[0]->getContext()['resetUrl'],
        );
    }

    public function testTheEmailSpellsOutWhatHappened(): void
    {
        $this->handler()($this->message());

        self::assertSame(
            'Your account was signed in from ' . self::DEVICE . '.',
            $this->sent[0]->getContext()['summary'],
        );
    }

    /**
     * A reset is the answer whatever was changed, and the same answer every time. It is the only thing that still
     * works once a password has been changed (every session is gone and the security page asks for the password
     * again), and it doubles as signing everyone else out, since changing a password invalidates every session.
     */
    public function testEveryNoticePointsAtAPasswordReset(): void
    {
        $types = [
            NotificationType::SignIn,
            NotificationType::PasswordChanged,
            NotificationType::MfaEnabled,
            NotificationType::MfaDisabled,
            NotificationType::BackupCodesRegenerated,
        ];

        $handler = $this->handler();
        foreach ($types as $type) {
            $handler($this->message(type: $type));
        }

        self::assertSame(
            array_fill(
                0,
                count($types),
                'https://gewis.nl/user_forgot_password',
            ),
            array_map(
                static fn (TemplatedEmail $email): mixed => $email->getContext()['resetUrl'],
                $this->sent,
            ),
        );
    }

    /**
     * A retry would run the handler again and leave a second notice in the notification centre, which reads far worse
     * than an email that did not arrive.
     */
    public function testAMailThatCannotBeSentDoesNotTakeTheNoticeWithIt(): void
    {
        $mailer = self::createStub(MailerInterface::class);
        $mailer->method('send')->willThrowException(new TransportException('nope'));

        $this->handler($mailer)($this->message());

        self::assertCount(
            1,
            $this->published,
        );
    }

    public function testNothingHappensForAFirewallWeNoLongerHave(): void
    {
        $this->handler()($this->message(firewallName: 'retired-firewall'));

        self::assertSame(
            [],
            $this->published,
        );
        self::assertSame(
            [],
            $this->sent,
        );
    }

    /**
     * There is nobody left to tell, and a notification cannot be addressed to an account that has gone.
     */
    public function testAnAccountThatNoLongerExistsIsNotNotifiedAtAll(): void
    {
        $this->handler()($this->message(userIdentifier: '1'));

        self::assertSame(
            [],
            $this->published,
        );
        self::assertSame(
            [],
            $this->sent,
        );
    }

    private function message(
        string $firewallName = 'main',
        string $userIdentifier = '8025',
        NotificationType $type = NotificationType::SignIn,
    ): SecurityNotificationMessage {
        return new SecurityNotificationMessage(
            $firewallName,
            $userIdentifier,
            $type,
            self::ORIGIN,
            new DateTimeImmutable('2026-07-31 12:00:00'),
        );
    }

    private function handler(?MailerInterface $mailer = null): SecurityNotificationHandler
    {
        if (null === $mailer) {
            $mailer = self::createStub(MailerInterface::class);
            $mailer->method('send')->willReturnCallback(function (RawMessage $email): void {
                self::assertInstanceOf(
                    TemplatedEmail::class,
                    $email,
                );
                $this->sent[] = $email;
            });
        }

        $entityManager = self::createStub(EntityManagerInterface::class);
        $entityManager->method('persist')->willReturnCallback(function (object $entity): void {
            self::assertInstanceOf(
                Notification::class,
                $entity,
            );
            $this->published[] = $entity;
        });

        $urlGenerator = self::createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(
            static fn (string $route): string => 'https://gewis.nl/' . $route,
        );

        $translator = self::createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            static fn (string $id, array $parameters = []): string => strtr(
                $id,
                $parameters,
            ),
        );

        return new SecurityNotificationHandler(
            new NotificationPublisher(
                $entityManager,
                new NullLogger(),
                [],
            ),
            $this->users(),
            $this->companyUsers(),
            $mailer,
            $urlGenerator,
            new NotificationContextResolver(new DeviceDescription($translator)),
            $translator,
            new NullLogger(),
        );
    }

    private function users(): UserRepository
    {
        $member = self::createStub(Member::class);
        $member->method('getEmail')->willReturn('ada@example.com');
        $member->method('getFullName')->willReturn('Ada Lovelace');

        $user = self::createStub(User::class);
        $user->method('getMember')->willReturn($member);

        $users = self::createStub(UserRepository::class);
        $users->method('find')->willReturnCallback(
            static fn (mixed $lidnr): ?User => 8025 === $lidnr ? $user : null,
        );

        return $users;
    }

    private function companyUsers(): CompanyUserRepository
    {
        $companyUser = self::createStub(CompanyUser::class);
        $companyUser->method('getCompany')->willReturn(self::createStub(Company::class));
        $companyUser->method('getEmail')->willReturn('rep@example.com');
        $companyUser->method('getName')->willReturn('Grace Hopper');

        $companyUsers = self::createStub(CompanyUserRepository::class);
        $companyUsers->method('loadUserByIdentifier')->willReturnCallback(
            static fn (string $identifier): ?CompanyUser => 'rep@example.com' === $identifier ? $companyUser : null,
        );

        return $companyUsers;
    }
}
