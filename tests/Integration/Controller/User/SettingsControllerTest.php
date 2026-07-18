<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\User;

use App\Controller\User\SettingsController;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Security\User\SudoMode;
use App\Service\Application\FileStorage;
use App\Tests\Integration\DatabaseTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * The settings page renders for an authenticated member with the privacy toggles and, because no sudo is active in a
 * fresh request, the irreversible tag purge routes through re-authentication rather than opening straight into the
 * confirmation modal. Invoked directly, mirroring the rest of the suite (the codebase has no WebTestCase).
 */
final class SettingsControllerTest extends DatabaseTestCase
{
    private const int MEMBER = 8030;

    public function testSettingsPageRendersWithTogglesAndASudoGatedPurge(): void
    {
        $this->authenticateMember();
        $request = $this->pushRequest();

        $user = $this->entityManager->getRepository(User::class)->find(self::MEMBER);
        self::assertInstanceOf(
            User::class,
            $user,
        );

        $response = self::getContainer()->get(SettingsController::class)->settings(
            $request,
            self::getContainer()->get(SudoMode::class),
            self::getContainer()->get(FileStorage::class),
            $user,
        );

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );

        $html = (string) $response->getContent();
        self::assertStringContainsString(
            'Do not allow others to tag me in photos',
            $html,
        );
        self::assertStringContainsString(
            'Hide my year of birth and age from other members',
            $html,
        );
        // The navbar cosmetics switch is rendered for a member user.
        self::assertStringContainsString(
            'Festive effects',
            $html,
        );
        // Sudo is not active for a fresh request, so the purge button links to re-authentication instead of the modal.
        self::assertStringContainsString(
            '/user/sudo',
            $html,
        );
    }

    private function pushRequest(): Request
    {
        $session = self::getContainer()->get('session.factory')->createSession();
        self::assertInstanceOf(
            FlashBagAwareSessionInterface::class,
            $session,
        );

        $request = new Request();
        $request->setSession($session);
        self::getContainer()->get('request_stack')->push($request);

        return $request;
    }

    private function authenticateMember(): void
    {
        $user = $this->entityManager->getRepository(User::class)->find(self::MEMBER);
        self::assertInstanceOf(
            User::class,
            $user,
            'The seed is expected to contain a user for the member.',
        );

        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $user,
                'main',
                [UserRoles::Member->value],
            ),
        );
    }
}
