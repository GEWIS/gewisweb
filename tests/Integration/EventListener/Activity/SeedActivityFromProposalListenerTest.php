<?php

declare(strict_types=1);

namespace App\Tests\Integration\EventListener\Activity;

use App\Entity\Activity\ActivityProposal;
use App\Entity\Activity\Enums\DateOptionStatus;
use App\Entity\Activity\Enums\ProposalStatus;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\User\User;
use App\Tests\Integration\DatabaseTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * What reserving a day actually does: it starts the activity the body now has to build, and lets go of every other day
 * that body was standing on.
 *
 * The connection between the two is the point of the whole feature. Without it a body that has just been given a day
 * would go to another screen and type its own proposal in again, and nothing would be able to tell whether a reserved
 * day is being used.
 */
final class SeedActivityFromProposalListenerTest extends DatabaseTestCase
{
    public function testReservingADayStartsTheActivityAndReleasesTheRest(): void
    {
        $this->authenticateBoard();

        $proposal = $this->aWaitingProposalWithSeveralDays();
        $chosen = $proposal->getDateOptions()->first();
        self::assertNotFalse($chosen);
        $proposal->setChosenOption($chosen);

        $this->workflow()->apply(
            $proposal,
            'schedule',
        );
        $this->entityManager->flush();

        $activity = $proposal->getActivity();
        self::assertNotNull(
            $activity,
            'Reserving a day has to start the activity it is for.',
        );

        $revision = $activity->getCurrentRevision();
        self::assertNotNull($revision);
        self::assertSame(
            RevisionStatus::Draft,
            $revision->getStatus(),
            'It is the body that finishes the activity, so it starts as their draft.',
        );
        self::assertSame(
            $proposal->getName(),
            $revision->getName()->getValueEN(),
        );
        self::assertSame(
            $proposal->getOrgan(),
            $revision->getOrgan(),
        );
        self::assertSame(
            $chosen->getBeginsAt()->format('Y-m-d'),
            $revision->getBeginTime()?->format('Y-m-d'),
        );

        foreach ($proposal->getDateOptions() as $dateOption) {
            $expected = $dateOption === $chosen
                ? DateOptionStatus::Approved
                : DateOptionStatus::Declined;

            self::assertSame(
                $expected,
                $dateOption->getStatus(),
                'Every day that was not picked has to be free for whoever is next in line.',
            );
        }
    }

    public function testTakingAProposalBackFreesItsDaysButKeepsTheActivity(): void
    {
        $this->authenticateBoard();

        $proposal = $this->entityManager->getRepository(ActivityProposal::class)->findOneBy([
            'status' => ProposalStatus::Scheduled,
        ]);
        self::assertInstanceOf(
            ActivityProposal::class,
            $proposal,
        );
        $activity = $proposal->getActivity();

        $this->workflow()->apply(
            $proposal,
            'withdraw',
        );
        $this->entityManager->flush();

        foreach ($proposal->getDateOptions() as $dateOption) {
            self::assertFalse(
                $dateOption->getStatus()->isStanding(),
                'A day nobody is holding must not stand in anybody else\'s way.',
            );
        }

        self::assertSame(
            $activity,
            $proposal->getActivity(),
            'Losing a day is no reason to throw away what somebody already wrote.',
        );
    }

    /**
     * Scheduling again after a reopen must not leave the body with two activities for one proposal.
     */
    public function testReopeningAndSchedulingAgainReusesTheSameActivity(): void
    {
        $this->authenticateBoard();

        $proposal = $this->entityManager->getRepository(ActivityProposal::class)->findOneBy([
            'status' => ProposalStatus::Scheduled,
        ]);
        self::assertInstanceOf(
            ActivityProposal::class,
            $proposal,
        );
        $activity = $proposal->getActivity();

        $this->workflow()->apply(
            $proposal,
            'withdraw',
        );
        $this->workflow()->apply(
            $proposal,
            'reopen',
        );
        $this->workflow()->apply(
            $proposal,
            'schedule',
        );
        $this->entityManager->flush();

        self::assertSame(
            $activity,
            $proposal->getActivity(),
        );
    }

    private function aWaitingProposalWithSeveralDays(): ActivityProposal
    {
        foreach (
            $this->entityManager->getRepository(ActivityProposal::class)->findBy([
                'status' => ProposalStatus::Submitted,
            ]) as $proposal
        ) {
            if (1 >= $proposal->getDateOptions()->count()) {
                continue;
            }

            return $proposal;
        }

        self::fail('The seed is expected to hold a waiting proposal that asked for more than one day.');
    }

    private function workflow(): WorkflowInterface
    {
        return self::getContainer()->get(Registry::class)->get(
            new ActivityProposal(),
            'activity_proposal',
        );
    }

    private function authenticateBoard(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy([]);
        self::assertInstanceOf(
            User::class,
            $user,
        );

        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $user,
            'main',
            ['ROLE_BOARD'],
        ));
    }
}
