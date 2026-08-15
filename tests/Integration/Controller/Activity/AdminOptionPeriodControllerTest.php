<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Activity;

use App\Controller\Activity\AdminOptionPeriodController;
use App\Entity\Activity\OptionPeriod;
use App\Entity\Activity\PeriodProposalLimit;
use App\Entity\Decision\Organ;
use App\Entity\User\User;
use App\Tests\Integration\DatabaseTestCase;
use DateTime;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * The board's rounds. Actions are invoked directly, as the other admin controller tests do: the app's session guard
 * logs out any synthetic browser session, so the `#[IsGranted]` board gate and the CSRF attributes are enforced by the
 * framework at the HTTP boundary rather than here.
 *
 * The one thing worth pinning beyond the ordinary create/edit is that a round holding proposals cannot be removed out
 * from under the bodies that are holding dates in it.
 */
final class AdminOptionPeriodControllerTest extends DatabaseTestCase
{
    public function testARoundWithProposalsIsNotRemoved(): void
    {
        $this->authenticate();
        $this->pushRequestWithSession();

        $period = $this->aPeriodWithProposals();
        $id = $period->getId();

        $response = $this->controller()->delete($period);

        self::assertInstanceOf(
            RedirectResponse::class,
            $response,
        );
        self::assertNotNull($this->entityManager->getRepository(OptionPeriod::class)->find($id));
    }

    public function testAnEmptyRoundIsRemovedWithItsExceptions(): void
    {
        $this->authenticate();
        $this->pushRequestWithSession();

        $period = new OptionPeriod();
        $period->setName('A round nobody used');
        $period->setSubmissionOpensAt(new DateTime('-1 day'));
        $period->setSubmissionClosesAt(new DateTime('+1 day'));
        $period->setStartsAt(new DateTime('+300 days'));
        $period->setEndsAt(new DateTime('+390 days'));
        $this->entityManager->persist($period);

        $limit = new PeriodProposalLimit();
        $limit->setPeriod($period);
        $limit->setOrgan($this->entityManager->getRepository(Organ::class)->findAll()[0]);
        $limit->setMaxProposals(1);
        $this->entityManager->persist($limit);
        $this->entityManager->flush();

        $periodId = $period->getId();
        $limitId = $limit->getId();

        $this->controller()->delete($period);

        self::assertNull($this->entityManager->getRepository(OptionPeriod::class)->find($periodId));
        self::assertNull($this->entityManager->getRepository(PeriodProposalLimit::class)->find($limitId));
    }

    public function testTheIndexListsEveryRound(): void
    {
        $this->authenticate();
        $this->pushRequestWithSession();

        $response = $this->controller()->index();

        self::assertTrue($response->isSuccessful());
        self::assertStringContainsString(
            'Q2 this year',
            (string) $response->getContent(),
        );
    }

    private function controller(): AdminOptionPeriodController
    {
        return self::getContainer()->get(AdminOptionPeriodController::class);
    }

    private function aPeriodWithProposals(): OptionPeriod
    {
        foreach ($this->entityManager->getRepository(OptionPeriod::class)->findAll() as $period) {
            if ($period->getProposals()->isEmpty()) {
                continue;
            }

            return $period;
        }

        self::fail('The seed is expected to hold a round with proposals in it.');
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

    private function pushRequestWithSession(): void
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
}
