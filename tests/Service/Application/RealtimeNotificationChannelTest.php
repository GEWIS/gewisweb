<?php

declare(strict_types=1);

namespace App\Tests\Service\Application;

use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Application\Enums\NotificationType;
use App\Entity\Application\Notification;
use App\Entity\User\CompanyUser;
use App\Entity\User\User;
use App\Repository\Activity\ActivityRepository;
use App\Repository\Activity\ActivityRevisionRepository;
use App\Repository\Photo\AlbumRepository;
use App\Service\Application\DeviceDescription;
use App\Service\Application\NotificationContextResolver;
use App\Service\Application\NotificationSubjectResolver;
use App\Service\Application\RealtimeNotificationChannel;
use App\Service\Application\RealtimeNotifier;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function json_decode;
use function strtr;

use const JSON_THROW_ON_ERROR;

final class RealtimeNotificationChannelTest extends TestCase
{
    /** @var list<Update> */
    private array $updates = [];

    public function testANotificationForEveryoneGoesOutOnThePublicTopic(): void
    {
        $this->channel()->deliver($this->notification(origin: ['browser' => 'Chrome 124', 'system' => 'Windows 11']));

        self::assertSame(
            ['gewis/public'],
            $this->updates[0]->getTopics(),
        );
        self::assertFalse($this->updates[0]->isPrivate());
    }

    public function testANotificationForOneMemberGoesOutOnTheirPrivateTopic(): void
    {
        $notification = $this->notification(origin: ['browser' => 'Chrome 124', 'system' => 'Windows 11']);
        $notification->setRecipient(
            $this->member('8025'),
            null,
        );

        $this->channel()->deliver($notification);

        self::assertSame(
            ['gewis/user/main/8025'],
            $this->updates[0]->getTopics(),
        );
        self::assertTrue($this->updates[0]->isPrivate());
    }

    public function testACompanyRecipientIsReachedOnTheCompanyFirewall(): void
    {
        $notification = $this->notification(origin: ['browser' => 'Chrome 124', 'system' => 'Windows 11']);
        $notification->setRecipient(
            null,
            $this->company('3'),
        );

        $this->channel()->deliver($notification);

        self::assertSame(
            ['gewis/user/company/3'],
            $this->updates[0]->getTopics(),
        );
        self::assertTrue($this->updates[0]->isPrivate());
    }

    /**
     * A notification naming a device has no subject to resolve, and dropping it would lose exactly the ones that
     * matter most: the account warnings that outlive whatever they describe.
     */
    public function testANotificationWithAnOriginButNoSubjectIsStillDelivered(): void
    {
        $this->channel()->deliver($this->notification(origin: ['browser' => 'Chrome 124', 'system' => 'Windows 11']));

        self::assertCount(
            1,
            $this->updates,
        );
        self::assertStringContainsString(
            'Chrome 124 on Windows 11',
            $this->payload()['message']['en'],
        );
    }

    public function testANotificationWithNeitherOriginNorResolvableSubjectSaysNothing(): void
    {
        $this->channel()->deliver($this->notification(subjectId: 7));

        self::assertSame(
            [],
            $this->updates,
        );
    }

    private function member(string $identifier): User
    {
        $user = self::createStub(User::class);
        $user->method('getUserIdentifier')->willReturn($identifier);

        return $user;
    }

    private function company(string $identifier): CompanyUser
    {
        $companyUser = self::createStub(CompanyUser::class);
        $companyUser->method('getUserIdentifier')->willReturn($identifier);

        return $companyUser;
    }

    /**
     * @param array<string, string>|null $origin
     */
    private function notification(
        ?int $subjectId = null,
        ?array $origin = null,
    ): Notification {
        $notification = new Notification();
        // A context only means something to a kind that keeps one, so these are sign-ins rather than albums.
        $notification->setType(
            null === $origin
                ? NotificationType::AlbumPublished
                : NotificationType::SignIn,
        );
        $notification->setLevel(AlertTypes::Warning);
        $notification->setSubjectId($subjectId);
        $notification->setContext($origin);

        return $notification;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return json_decode(
            $this->updates[0]->getData(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }

    private function channel(): RealtimeNotificationChannel
    {
        $hub = self::createStub(HubInterface::class);
        $hub->method('publish')->willReturnCallback(function (Update $update): string {
            $this->updates[] = $update;

            return 'id';
        });

        // The resolver is final, so it is built for real over repositories that find nothing: these tests are about
        // which topic a notification lands on and whether a frozen label stands in for a subject, not about lookups.
        $albums = self::createStub(AlbumRepository::class);
        $albums->method('findBy')->willReturn([]);
        $activities = self::createStub(ActivityRepository::class);
        $activities->method('findBy')->willReturn([]);
        $revisions = self::createStub(ActivityRevisionRepository::class);
        $revisions->method('findBy')->willReturn([]);

        $translator = self::createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            static fn (string $id, array $parameters = []): string => strtr(
                $id,
                $parameters,
            ),
        );

        $urlGenerator = self::createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(
            static fn (string $route, array $parameters): string => 'https://gewis.nl/'
                . $route . '/' . $parameters['_locale'],
        );

        return new RealtimeNotificationChannel(
            new RealtimeNotifier($hub),
            new NotificationSubjectResolver(
                $albums,
                $activities,
                $revisions,
            ),
            new NotificationContextResolver(new DeviceDescription($translator)),
            $translator,
            $urlGenerator,
        );
    }
}
