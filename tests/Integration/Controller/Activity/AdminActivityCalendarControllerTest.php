<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Activity;

use App\Controller\Activity\AdminActivityCalendarController;
use App\Entity\Activity\ActivityDateOption;
use App\Entity\Activity\ActivityProposal;
use App\Entity\Activity\Enums\ProposalStatus;
use App\Entity\Activity\OptionPeriod;
use App\Entity\Decision\Organ;
use App\Entity\User\User;
use App\Tests\Integration\DatabaseTestCase;
use DateTime;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use function count;

/**
 * Handing a proposal in, against the seeded calendar.
 *
 * The seed holds GETÉST to two in the round that is taking proposals and has it use both, and puts KEUR on a standing
 * two with one used, so one body has room and the other does not. Actions are invoked directly, as the other admin
 * controller tests do.
 */
final class AdminActivityCalendarControllerTest extends DatabaseTestCase
{
    public function testABodyWithRoomCanHandOneIn(): void
    {
        $this->authenticate();
        $before = $this->proposalCount();

        $response = $this->submit(
            $this->body('KEUR'),
            'Een extra borrel',
        );

        self::assertInstanceOf(
            RedirectResponse::class,
            $response,
        );
        self::assertSame(
            $before + 1,
            $this->proposalCount(),
        );

        $stored = $this->entityManager->getRepository(ActivityProposal::class)->findOneBy([
            'name' => 'Een extra borrel',
        ]);
        self::assertInstanceOf(
            ActivityProposal::class,
            $stored,
        );
        self::assertSame(
            ProposalStatus::Submitted,
            $stored->getStatus(),
        );
        self::assertCount(
            1,
            $stored->getDateOptions(),
        );
        $first = $stored->getDateOptions()->first();
        self::assertInstanceOf(
            ActivityDateOption::class,
            $first,
        );
        // The order the days were put in is the body's order of preference, numbered from one.
        self::assertSame(
            1,
            $first->getPosition(),
        );
    }

    public function testABodyOnItsLimitIsRefusedAndToldWhy(): void
    {
        $this->authenticate();
        $before = $this->proposalCount();

        $response = $this->submit(
            $this->body('GETÉST'),
            'Een activiteit te veel',
        );

        self::assertFalse($response instanceof RedirectResponse);
        self::assertSame(
            $before,
            $this->proposalCount(),
        );
        self::assertStringContainsString(
            'already put forward everything it may',
            (string) $response->getContent(),
        );
    }

    public function testADayOutsideTheRoundIsRefused(): void
    {
        $this->authenticate();
        $before = $this->proposalCount();

        $period = $this->openPeriod();
        $response = $this->submit(
            $this->body('KEUR'),
            'Ver buiten de ronde',
            (clone $period->getEndsAt())->modify('+30 days'),
        );

        self::assertFalse($response instanceof RedirectResponse);
        self::assertSame(
            $before,
            $this->proposalCount(),
        );
        self::assertStringContainsString(
            'This round only covers',
            (string) $response->getContent(),
        );
    }

    private function submit(
        Organ $organ,
        string $name,
        ?DateTime $day = null,
    ): mixed {
        $period = $this->openPeriod();
        $day ??= (clone $period->getStartsAt())->modify('+3 days');

        // Stateless CSRF: the rendered token really is the literal `csrf-token`, and the manager only accepts it
        // together with a same-origin fetch header. Both are needed, neither alone is enough.
        $request = Request::create(
            '/en/admin/activities/calendar/propose',
            'POST',
            [
                'activity_proposal' => [
                    '_csrf_token' => 'csrf-token',
                    'organ' => (string) $organ->getId(),
                    'period' => (string) $period->getId(),
                    'name' => $name,
                    'description' => '',
                    'dateOptions' => [
                        [
                            'timeOfDay' => 'evening',
                            'beginsAt' => $day->format('Y-m-d'),
                            'endsAt' => $day->format('Y-m-d'),
                        ],
                    ],
                ],
            ],
        );
        $request->headers->set(
            'Sec-Fetch-Site',
            'same-origin',
        );
        $this->pushRequest($request);

        return $this->controller()->propose(
            $request,
            $this->user(8025),
        );
    }

    private function controller(): AdminActivityCalendarController
    {
        return self::getContainer()->get(AdminActivityCalendarController::class);
    }

    private function openPeriod(): OptionPeriod
    {
        $periods = $this->entityManager->getRepository(OptionPeriod::class)->findOpenAt(new DateTime());

        self::assertNotEmpty(
            $periods,
            'The seed is expected to hold a round that is taking proposals.',
        );

        return $periods[0];
    }

    private function body(string $abbr): Organ
    {
        $organ = $this->entityManager->getRepository(Organ::class)->findOneBy(['abbr' => $abbr]);

        self::assertInstanceOf(
            Organ::class,
            $organ,
        );

        return $organ;
    }

    private function proposalCount(): int
    {
        return count($this->entityManager->getRepository(ActivityProposal::class)->findAll());
    }

    private function authenticate(int $lidnr = 8025): void
    {
        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $this->user($lidnr),
            'main',
            ['ROLE_BOARD'],
        ));
    }

    private function user(int $lidnr): User
    {
        $user = $this->entityManager->getRepository(User::class)->find($lidnr);
        self::assertInstanceOf(
            User::class,
            $user,
        );

        return $user;
    }

    private function pushRequest(Request $request): void
    {
        $session = self::getContainer()->get('session.factory')->createSession();
        self::assertInstanceOf(
            FlashBagAwareSessionInterface::class,
            $session,
        );

        $request->setSession($session);
        self::getContainer()->get('request_stack')->push($request);
    }
}
