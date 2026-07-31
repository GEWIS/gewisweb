<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\User;

use App\Controller\User\SettingsController;
use App\Entity\Application\Enums\NotificationEmailFrequency;
use App\Entity\Application\Enums\NotificationType;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\User\NotificationEmailSubscriptionRepository;
use App\Repository\User\UserSettingsRepository;
use App\Tests\Integration\DatabaseTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * The member notification-settings page, invoked directly (the codebase has no WebTestCase). The class-level ROLE_USER
 * guard is enforced at the HTTP layer, so a direct call exercises the action body, its per-category form and its
 * template.
 */
final class SettingsControllerNotificationsTest extends DatabaseTestCase
{
    public function testTheNotificationsPageRenders(): void
    {
        $user = $this->authenticate();
        $this->pushRequest();

        $response = self::getContainer()->get(SettingsController::class)->notifications(
            new Request(),
            $user,
        );

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );
        $content = (string) $response->getContent();
        // A per-topic row and the interactive table are rendered.
        self::assertStringContainsString(
            'New photo albums',
            $content,
        );
        self::assertStringContainsString(
            'data-controller="notification-settings"',
            $content,
        );
        self::assertStringContainsString(
            'As they happen',
            $content,
        );
    }

    public function testSavingStoresPerCategoryFrequencyAndPause(): void
    {
        $user = $this->authenticate();
        $session = $this->pushRequest();

        $token = self::getContainer()->get(CsrfTokenManagerInterface::class)
            ->getToken('notification_settings')
            ->getValue();

        $request = Request::create(
            '/en/user/settings/notifications',
            'POST',
            [
                '_token' => $token,
                'categories' => [NotificationType::AlbumPublished->value],
                'frequency' => [
                    NotificationType::AlbumPublished->value => NotificationEmailFrequency::Weekly->value,
                    NotificationType::ActivityPublished->value => NotificationEmailFrequency::Daily->value,
                ],
                'paused' => '1',
            ],
        );
        $request->setSession($session);
        self::getContainer()->get('request_stack')->push($request);

        $response = self::getContainer()->get(SettingsController::class)->notifications(
            $request,
            $user,
        );

        self::assertSame(
            Response::HTTP_FOUND,
            $response->getStatusCode(),
        );

        // Only the enabled topic is stored, at the frequency it was given.
        $subscriptions = self::getContainer()->get(NotificationEmailSubscriptionRepository::class)->findForUser($user);
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

        $settings = self::getContainer()->get(UserSettingsRepository::class)->getOrCreateForUser($user);
        self::assertTrue($settings->getNotificationsPaused());
    }

    private function authenticate(): User
    {
        $user = $this->entityManager->getRepository(User::class)->find(8025);
        self::assertInstanceOf(
            User::class,
            $user,
        );

        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $user,
                'main',
                [UserRoles::User->value],
            ),
        );

        return $user;
    }

    private function pushRequest(): SessionInterface
    {
        $session = self::getContainer()->get('session.factory')->createSession();
        self::assertInstanceOf(
            FlashBagAwareSessionInterface::class,
            $session,
        );

        $request = new Request();
        $request->setSession($session);
        self::getContainer()->get('request_stack')->push($request);

        return $session;
    }
}
