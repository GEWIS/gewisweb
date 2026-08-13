<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Frontpage;

use App\Controller\Frontpage\AdminController;
use App\Entity\User\User;
use App\Tests\Integration\DatabaseTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use function strval;

/**
 * The dashboard is reachable by any active member, so every block on it is gated on its own. These read the page as
 * each kind of reader gets it: a queue they cannot open is worse than no queue.
 *
 * Everything waiting is one list the reader narrows with a link per area, so a queue is matched by the link that
 * narrows to it rather than by its name, which the navigation around the page answers for too.
 */
final class AdminControllerTest extends DatabaseTestCase
{
    /**
     * The board reviews everything, careers included: its role carries the committee's, which is what the voter reads
     * when it decides who may approve a company profile.
     */
    public function testTheBoardSeesEveryQueue(): void
    {
        $content = $this->dashboardFor(['ROLE_BOARD']);

        foreach (
            [
                'queue=activities',
                'queue=bodies',
                'queue=polls',
                'queue=companies',
                'queue=vacancies',
                'Waiting on you',
            ] as $expected
        ) {
            self::assertStringContainsString(
                $expected,
                $content,
            );
        }
    }

    /**
     * The committee's role does not carry the board's, so what the association says about itself is not on its page.
     */
    public function testTheCareersCommitteeSeesItsOwnQueuesAndNoOthers(): void
    {
        $content = $this->dashboardFor(['ROLE_COMPANY_ADMIN']);

        self::assertStringContainsString(
            'queue=companies',
            $content,
        );
        self::assertStringContainsString(
            'queue=vacancies',
            $content,
        );
        self::assertStringNotContainsString(
            'queue=bodies',
            $content,
        );
    }

    /**
     * Narrowed to one area, the list points at that area's own queue. Across every area there is no one queue to
     * point at, so it says nothing rather than sending the reader somewhere arbitrary.
     */
    public function testNarrowingToOneAreaPointsAtItsOwnQueue(): void
    {
        self::assertStringContainsString(
            'Open the full queue',
            $this->dashboardFor(
                ['ROLE_BOARD'],
                'polls',
            ),
        );
        self::assertStringNotContainsString(
            'Open the full queue',
            $this->dashboardFor(['ROLE_BOARD']),
        );
    }

    /**
     * An active member with nothing waiting on them still gets a page, and it says so rather than showing an empty
     * dashboard that reads as if something has gone missing.
     */
    public function testAnActiveMemberGetsAPageWithNoQueuesOnIt(): void
    {
        $content = $this->dashboardFor(['ROLE_ACTIVE_MEMBER']);

        self::assertStringContainsString(
            'nothing here waiting on you',
            $content,
        );
        self::assertStringNotContainsString(
            'Waiting on you',
            $content,
        );
    }

    /**
     * What the whole association is being told, and whether the website is in maintenance, are the administrator's to
     * see and nobody else's.
     */
    public function testOnlyAnAdministratorSeesTheAnnouncementsAndMaintenance(): void
    {
        self::assertStringNotContainsString(
            'Site state',
            $this->dashboardFor(['ROLE_BOARD']),
        );
        self::assertStringContainsString(
            'Site state',
            $this->dashboardFor([
                'ROLE_ADMIN',
                'ROLE_BOARD',
            ]),
        );
    }

    /**
     * @param string[] $roles
     */
    private function dashboardFor(
        array $roles,
        ?string $queue = null,
    ): string {
        $user = $this->entityManager->getRepository(User::class)->find(8000);
        self::assertInstanceOf(
            User::class,
            $user,
        );

        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $user,
            'main',
            $roles,
        ));

        $session = self::getContainer()->get('session.factory')->createSession();
        self::assertInstanceOf(
            FlashBagAwareSessionInterface::class,
            $session,
        );

        $request = Request::create('/en/admin');
        $request->setSession($session);
        self::getContainer()->get('request_stack')->push($request);

        $response = self::getContainer()->get(AdminController::class)->index($queue);
        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
        );

        return strval($response->getContent());
    }
}
