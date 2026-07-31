<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Application;

use App\Controller\Application\AnnouncementController;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Tests\Integration\DatabaseTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * The announcement admin controller, invoked directly (the codebase has no WebTestCase). The class-level admin/sudo
 * guards are enforced at the HTTP layer, so a direct call exercises the action body and its form and template.
 */
final class AnnouncementControllerTest extends DatabaseTestCase
{
    public function testCreatePageRenders(): void
    {
        $this->authenticateAdmin();
        $this->pushRequest();

        $response = $this->controller()->create(new Request());

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );
        self::assertStringContainsString(
            'Send an announcement',
            (string) $response->getContent(),
        );
    }

    private function controller(): AnnouncementController
    {
        return self::getContainer()->get(AnnouncementController::class);
    }

    private function pushRequest(): void
    {
        $session = self::getContainer()->get('session.factory')->createSession();
        self::assertInstanceOf(
            FlashBagAwareSessionInterface::class,
            $session,
        );

        $request = new Request();
        $request->setSession($session);
        self::getContainer()->get('request_stack')->push($request);
    }

    private function authenticateAdmin(): void
    {
        $user = $this->entityManager->getRepository(User::class)->find(8025);
        self::assertInstanceOf(
            User::class,
            $user,
            'The seed is expected to contain a board member.',
        );

        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                $user,
                'main',
                [UserRoles::Admin->value],
            ),
        );
    }
}
