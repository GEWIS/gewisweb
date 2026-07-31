<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\User;

use App\Controller\User\UserController;
use App\Entity\Application\Enums\NotificationType;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Message\User\SecurityNotificationMessage;
use App\Repository\User\ExternalAppAuthenticationRepository;
use App\Security\User\MfaPolicy;
use App\Service\User\SecurityNotifier;
use App\Tests\Integration\DatabaseTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use function array_map;

/**
 * Changing how you sign in has to tell you it happened, whether or not you were the one who did it. The action is
 * invoked directly (the codebase has no WebTestCase); the sudo guard on it is enforced at the HTTP layer.
 *
 * The seeded members all share the password below, hashed at the reduced cost dev and test configure.
 */
final class SecurityControllerNoticesTest extends DatabaseTestCase
{
    private const string PASSWORD = 'gewiswebgewis';

    public function testChangingYourPasswordRaisesANotice(): void
    {
        $this->changePassword(self::PASSWORD);

        self::assertSame(
            [NotificationType::PasswordChanged],
            $this->raised(),
        );
    }

    public function testTheNoticeNamesTheMemberAndTheirFirewall(): void
    {
        $this->changePassword(self::PASSWORD);

        $message = $this->messages()[0];
        self::assertSame(
            'main',
            $message->getFirewallName(),
        );
        self::assertSame(
            '8025',
            $message->getUserIdentifier(),
        );
        self::assertNotSame(
            [],
            $message->getOrigin(),
        );
    }

    /**
     * A rejected attempt changed nothing, so there is nothing to report and no reason to alarm anybody.
     */
    public function testAFailedAttemptRaisesNothing(): void
    {
        $this->changePassword('not-the-current-password');

        self::assertSame(
            [],
            $this->raised(),
        );
    }

    private function changePassword(string $currentPassword): void
    {
        $user = $this->entityManager->getRepository(User::class)->find(8025);
        self::assertInstanceOf(
            User::class,
            $user,
        );

        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $user,
            'main',
            [UserRoles::User->value],
        ));

        $request = Request::create(
            '/en/user/security',
            'POST',
            [
                'change_password_form' => [
                    // Forms use stateless CSRF: the field carries a sentinel and the origin does the proving.
                    '_csrf_token' => 'csrf-token',
                    'currentPassword' => $currentPassword,
                    'plainPassword' => [
                        'first' => 'Correct-Horse-Battery-9',
                        'second' => 'Correct-Horse-Battery-9',
                    ],
                ],
            ],
        );
        // Forms are protected by stateless CSRF, which accepts a request the browser reports as same-origin.
        $request->headers->set(
            'Sec-Fetch-Site',
            'same-origin',
        );
        $request->setSession($this->session());
        self::getContainer()->get('request_stack')->push($request);

        $controller = self::getContainer()->get(UserController::class);
        $controller->setContainer(self::getContainer());
        $controller->security(
            $request,
            self::getContainer()->get(UserPasswordHasherInterface::class),
            self::getContainer()->get(EntityManagerInterface::class),
            self::getContainer()->get(MfaPolicy::class),
            self::getContainer()->get(ExternalAppAuthenticationRepository::class),
            self::getContainer()->get(SecurityNotifier::class),
            $user,
        );
    }

    /**
     * @return list<NotificationType>
     */
    private function raised(): array
    {
        return array_map(
            static fn (SecurityNotificationMessage $message): NotificationType => $message->getType(),
            $this->messages(),
        );
    }

    /**
     * @return list<SecurityNotificationMessage>
     */
    private function messages(): array
    {
        $transport = self::getContainer()->get('messenger.transport.high_priority');
        self::assertInstanceOf(
            InMemoryTransport::class,
            $transport,
        );

        $messages = [];
        foreach ($transport->getSent() as $envelope) {
            $message = $envelope->getMessage();

            if (!$message instanceof SecurityNotificationMessage) {
                continue;
            }

            $messages[] = $message;
        }

        return $messages;
    }

    private function session(): SessionInterface
    {
        $session = self::getContainer()->get('session.factory')->createSession();
        self::assertInstanceOf(
            FlashBagAwareSessionInterface::class,
            $session,
        );

        return $session;
    }
}
