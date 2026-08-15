<?php

declare(strict_types=1);

namespace App\Tests\Integration\Workflow;

use App\Entity\Activity\ActivityProposal;
use App\Entity\Activity\Enums\BudgetClearance;
use App\Entity\Activity\Enums\ProposalStatus;
use App\Entity\User\User;
use App\Tests\Integration\DatabaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * The `activity_proposal` state machine against the seeded calendar: that the marking really is the proposal's status
 * column, that a date goes from waiting to held to settled, and that nothing is a dead end.
 *
 * The last part is the one worth pinning. A lapsed date, a proposal the board turned down and one a body took back can
 * all be put back in the running, and a clearance recorded by mistake can be taken back, so no state the board can
 * reach leaves them with nothing to do but ask an administrator.
 */
final class ActivityProposalWorkflowTest extends DatabaseTestCase
{
    public function testTheMarkingIsTheStatusColumn(): void
    {
        $this->authenticateBoard();
        $proposal = $this->proposalWith(ProposalStatus::Submitted);

        self::assertTrue($this->workflow()->getMarking($proposal)->has(ProposalStatus::Submitted->value));

        $this->workflow()->apply(
            $proposal,
            'schedule',
        );

        self::assertSame(
            ProposalStatus::Scheduled,
            $proposal->getStatus(),
        );
        self::assertTrue($this->workflow()->getMarking($proposal)->has(ProposalStatus::Scheduled->value));
    }

    public function testADateGoesFromWaitingToHeldToSettled(): void
    {
        $this->authenticateBoard();
        $proposal = $this->proposalWith(ProposalStatus::Submitted);

        $this->workflow()->apply(
            $proposal,
            'schedule',
        );
        $this->workflow()->apply(
            $proposal,
            'clear_budget',
        );

        self::assertSame(
            ProposalStatus::Cleared,
            $proposal->getStatus(),
        );
    }

    public function testAnUnsettledDateCanLapseAndASettledOneCannot(): void
    {
        $this->authenticateBoard();

        $scheduled = $this->proposalWith(ProposalStatus::Scheduled);
        self::assertTrue($this->workflow()->can(
            $scheduled,
            'lapse',
        ));

        $cleared = $this->proposalWith(ProposalStatus::Cleared);
        self::assertFalse(
            $this->workflow()->can(
                $cleared,
                'lapse',
            ),
            'A proposal whose financial side is settled must never be expired, and that includes one that costs '
            . 'nothing.',
        );
    }

    /**
     * @param string[] $expected
     */
    #[DataProvider('deadEndCandidates')]
    public function testNoTerminalStateIsADeadEnd(
        ProposalStatus $status,
        array $expected,
    ): void {
        $this->authenticateBoard();
        $proposal = $this->proposalWith($status);

        foreach ($expected as $transition) {
            self::assertTrue(
                $this->workflow()->can(
                    $proposal,
                    $transition,
                ),
                $status->value . ' should offer ' . $transition,
            );
        }
    }

    /**
     * @return array<string, array{ProposalStatus, string[]}>
     */
    public static function deadEndCandidates(): array
    {
        return [
            'lapsed' => [
                ProposalStatus::Lapsed,
                ['reopen'],
            ],
            'declined' => [
                ProposalStatus::Declined,
                ['reopen'],
            ],
            'withdrawn' => [
                ProposalStatus::Withdrawn,
                ['reopen'],
            ],
            'cleared' => [
                ProposalStatus::Cleared,
                [
                    'revoke_clearance',
                    'withdraw',
                ],
            ],
        ];
    }

    public function testABodyMemberCannotDecideButCanWithdrawItsOwn(): void
    {
        $proposal = $this->proposalWith(ProposalStatus::Submitted);
        $this->authenticateAs(
            $proposal->getCreatedBy()->getLidnr(),
            ['ROLE_ACTIVE_MEMBER'],
        );

        self::assertFalse($this->workflow()->can(
            $proposal,
            'schedule',
        ));
        self::assertTrue($this->workflow()->can(
            $proposal,
            'withdraw',
        ));
    }

    private function workflow(): WorkflowInterface
    {
        return self::getContainer()->get(Registry::class)->get(
            new ActivityProposal(),
            'activity_proposal',
        );
    }

    private function proposalWith(ProposalStatus $status): ActivityProposal
    {
        $proposal = $this->entityManager->getRepository(ActivityProposal::class)->findOneBy(['status' => $status]);

        self::assertInstanceOf(
            ActivityProposal::class,
            $proposal,
            'The seed is expected to hold a proposal in every state.',
        );

        // A settled proposal is settled either way; the seed holds one of each and both must be immune to lapsing.
        if (ProposalStatus::Cleared === $status) {
            self::assertInstanceOf(
                BudgetClearance::class,
                $proposal->getBudgetClearance(),
            );
        }

        return $proposal;
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

    /**
     * @param string[] $roles
     */
    private function authenticateAs(
        int $lidnr,
        array $roles,
    ): void {
        $user = $this->entityManager->getRepository(User::class)->find($lidnr);
        self::assertInstanceOf(
            User::class,
            $user,
        );

        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $user,
            'main',
            $roles,
        ));
    }
}
