<?php

declare(strict_types=1);

namespace App\DataFixtures\Activity;

use App\DataFixtures\Decision\DecisionFixture;
use App\DataFixtures\Decision\MemberFixture;
use App\Entity\Activity\Activity;
use App\Entity\Activity\ActivityDateOption;
use App\Entity\Activity\ActivityLocalisedText;
use App\Entity\Activity\ActivityProposal;
use App\Entity\Activity\ActivityRevision;
use App\Entity\Activity\Enums\ActivityCategories;
use App\Entity\Activity\Enums\BudgetClearance;
use App\Entity\Activity\Enums\DateOptionStatus;
use App\Entity\Activity\Enums\ProposalStatus;
use App\Entity\Activity\Enums\TimeOfDay;
use App\Entity\Activity\OptionPeriod;
use App\Entity\Activity\PeriodProposalLimit;
use App\Entity\Activity\ProposalLimit;
use App\Entity\Decision\Member;
use App\Entity\Decision\Organ;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Override;
use RuntimeException;

use function sprintf;

/**
 * Seeds the option calendar: three rounds, two exceptions to the usual allowance, and proposals in every state.
 *
 * Deliberately seeded so the allowance is worth looking at. In the round that is taking proposals, GETÉST is held to
 * two for that round alone and has used both, KEUR is on a standing limit of two and has one left, and the board is
 * held to nothing. Nothing at all is seeded for a body without an exception, because a body without an exception is
 * meant to work without a row anywhere.
 *
 * There are deliberately no board meetings seeded ahead of today near any of this: the association records a board
 * meeting after it has happened, so nothing here may be built on knowing when the next one is.
 */
class OptionCalendarFixture extends Fixture implements DependentFixtureInterface
{
    public const string REFERENCE_PERIOD_PAST = 'option-period-past';
    public const string REFERENCE_PERIOD_RUNNING = 'option-period-running';
    public const string REFERENCE_PERIOD_OPEN = 'option-period-open';

    public const string REFERENCE_PROPOSAL_CONTESTED_FIRST = 'activity-proposal-contested-first';
    public const string REFERENCE_PROPOSAL_CONTESTED_SECOND = 'activity-proposal-contested-second';
    public const string REFERENCE_PROPOSAL_CONTESTED_THIRD = 'activity-proposal-contested-third';
    public const string REFERENCE_PROPOSAL_SECOND_OF_TWO = 'activity-proposal-second-of-two';
    public const string REFERENCE_PROPOSAL_SCHEDULED = 'activity-proposal-scheduled';
    public const string REFERENCE_PROPOSAL_CLEARED_BUDGET = 'activity-proposal-cleared-budget';
    public const string REFERENCE_PROPOSAL_CLEARED_FREE = 'activity-proposal-cleared-free';
    public const string REFERENCE_PROPOSAL_LAPSED = 'activity-proposal-lapsed';
    public const string REFERENCE_PROPOSAL_WITHDRAWN = 'activity-proposal-withdrawn';
    public const string REFERENCE_PROPOSAL_DECLINED = 'activity-proposal-declined';

    /**
     * Proposals whose creation moment has to be older than the moment they were seeded, because the order they were
     * handed in is what first dibs reads. Applied after the flush; the trait stamps `createdAt` itself on persist and
     * keeps its setter to itself.
     *
     * @var array<array-key, array{ActivityProposal, DateTime}>
     */
    private array $backdated = [];

    #[Override]
    public function load(ObjectManager $manager): void
    {
        $getest = $this->getReference(
            'organ-getest',
            Organ::class,
        );
        $keur = $this->getReference(
            'organ-keur',
            Organ::class,
        );
        // 8010 sits in GETÉST, 8025 in KEUR (and on the board), 8026 is the board member who decides.
        $getestMember = $this->getReference(
            'member-8010',
            Member::class,
        );
        $keurMember = $this->getReference(
            'member-8025',
            Member::class,
        );
        $boardMember = $this->getReference(
            'member-8026',
            Member::class,
        );

        $past = $this->period(
            'Q4 last year',
            -270,
            -240,
            -210,
            -120,
        );
        // Its days are running now, so this is where everything already decided lives.
        $running = $this->period(
            'Q1 this year',
            -90,
            -60,
            -30,
            60,
        );
        // Taking proposals right now.
        $open = $this->period(
            'Q2 this year',
            -14,
            14,
            70,
            160,
        );

        $manager->persist($past);
        $manager->persist($running);
        $manager->persist($open);

        $this->addReference(
            self::REFERENCE_PERIOD_PAST,
            $past,
        );
        $this->addReference(
            self::REFERENCE_PERIOD_RUNNING,
            $running,
        );
        $this->addReference(
            self::REFERENCE_PERIOD_OPEN,
            $open,
        );

        // KEUR is a small body and the board holds it to two activities a quartile, every quartile.
        $standing = new ProposalLimit();
        $standing->setOrgan($keur);
        $standing->setMaxProposals(2);
        $manager->persist($standing);

        // GETÉST is held to two in the round that is open alone, because that quartile is already busy.
        $override = new PeriodProposalLimit();
        $override->setPeriod($open);
        $override->setOrgan($getest);
        $override->setMaxProposals(2);
        $manager->persist($override);

        // Three bodies want the same day, which is what the queue orders by who asked first.
        $contestedDay = $this->days(96);

        $first = $this->proposal(
            $open,
            $getest,
            $getestMember,
            'Lasergamecompetitie',
            'Wij hebben de zaal al voorlopig vastgelegd.',
            -10,
        );
        $this->option(
            $first,
            1,
            $contestedDay,
            $contestedDay,
            TimeOfDay::Evening,
        );
        $this->option(
            $first,
            2,
            $this->days(103),
            $this->days(103),
            TimeOfDay::Evening,
        );
        $this->option(
            $first,
            3,
            $this->days(110),
            $this->days(110),
            TimeOfDay::Evening,
        );
        $manager->persist($first);

        $second = $this->proposal(
            $open,
            $keur,
            $keurMember,
            'Beleidsborrel',
            null,
            -8,
        );
        $this->option(
            $second,
            1,
            $contestedDay,
            $contestedDay,
            TimeOfDay::Evening,
        );
        $this->option(
            $second,
            2,
            $this->days(97),
            $this->days(97),
            TimeOfDay::Evening,
        );
        $manager->persist($second);

        // The board hosts this one itself, so it names no body and no allowance applies to it.
        $third = $this->proposal(
            $open,
            null,
            $boardMember,
            'Bestuursdiner',
            null,
            -5,
        );
        $this->option(
            $third,
            1,
            $contestedDay,
            $contestedDay,
            TimeOfDay::Evening,
        );
        $manager->persist($third);

        // GETÉST's second in the open round, which is what puts it on its limit of two.
        $fourth = $this->proposal(
            $open,
            $getest,
            $getestMember,
            'Kerstborrel',
            null,
            -3,
        );
        $this->option(
            $fourth,
            1,
            $this->days(120),
            $this->days(120),
            TimeOfDay::Evening,
        );
        $manager->persist($fourth);

        // Holding a date, with the activity itself started off as a draft and still to be filled in.
        $scheduled = $this->proposal(
            $running,
            $getest,
            $getestMember,
            'Nieuwjaarsborrel',
            null,
            -70,
        );
        $scheduledOption = $this->option(
            $scheduled,
            1,
            $this->days(40),
            $this->days(40),
            TimeOfDay::Evening,
        );
        $this->option(
            $scheduled,
            2,
            $this->days(47),
            $this->days(47),
            TimeOfDay::Evening,
        );
        $this->reserve(
            $scheduled,
            $scheduledOption,
            $boardMember,
        );
        $scheduled->setActivity($this->draftActivity(
            $manager,
            $getestMember,
            $getest,
            $scheduled->getName(),
            $scheduledOption,
        ));
        $manager->persist($scheduled);

        // Budget approved at a board meeting: nothing chases this one any more.
        $clearedBudget = $this->proposal(
            $running,
            $keur,
            $keurMember,
            'Kascontrole-uitje',
            null,
            -68,
        );
        $clearedBudgetOption = $this->option(
            $clearedBudget,
            1,
            $this->days(21),
            $this->days(21),
            TimeOfDay::Day,
        );
        $this->reserve(
            $clearedBudget,
            $clearedBudgetOption,
            $boardMember,
        );
        $this->clear(
            $clearedBudget,
            BudgetClearance::Approved,
            $boardMember,
        );
        $manager->persist($clearedBudget);

        // Costs nothing, so there was never a budget to hand in, and it must not be chased for one either.
        $clearedFree = $this->proposal(
            $running,
            $getest,
            $getestMember,
            'Wandeling door de Genneper Parken',
            'Geen kosten, we lopen gewoon.',
            -66,
        );
        $clearedFreeOption = $this->option(
            $clearedFree,
            1,
            $this->days(14),
            $this->days(14),
            TimeOfDay::Afternoon,
        );
        $this->reserve(
            $clearedFree,
            $clearedFreeOption,
            $boardMember,
        );
        $this->clear(
            $clearedFree,
            BudgetClearance::NotRequired,
            $boardMember,
        );
        $manager->persist($clearedFree);

        // Held a date, never settled the financial side, and ran out of road; the day is free for the next in line.
        $lapsed = $this->proposal(
            $running,
            $getest,
            $getestMember,
            'Vergeten activiteit',
            null,
            -80,
        );
        $lapsedOption = $this->option(
            $lapsed,
            1,
            $this->days(7),
            $this->days(7),
            TimeOfDay::Evening,
        );
        $this->reserve(
            $lapsed,
            $lapsedOption,
            $boardMember,
        );
        $lapsed->setStatus(ProposalStatus::Lapsed);
        $lapsedOption->setStatus(DateOptionStatus::Declined);
        $lapsed->setBudgetRemindedAt($this->days(-21));
        $manager->persist($lapsed);

        $withdrawn = $this->proposal(
            $running,
            $keur,
            $keurMember,
            'Ingetrokken plan',
            null,
            -75,
        );
        $this->option(
            $withdrawn,
            1,
            $this->days(30),
            $this->days(32),
            TimeOfDay::MultipleDays,
        );
        $this->option(
            $withdrawn,
            2,
            $this->days(37),
            $this->days(39),
            TimeOfDay::MultipleDays,
        );
        $withdrawn->setStatus(ProposalStatus::Withdrawn);
        $this->settleRemainingOptions(
            $withdrawn,
            DateOptionStatus::Withdrawn,
        );
        $manager->persist($withdrawn);

        $declined = $this->proposal(
            $past,
            $getest,
            $getestMember,
            'Afgewezen plan',
            null,
            -250,
        );
        $this->option(
            $declined,
            1,
            $this->days(-150),
            $this->days(-150),
            TimeOfDay::LunchBreak,
        );
        $declined->setStatus(ProposalStatus::Declined);
        $declined->setDecidedBy($boardMember);
        $declined->setDecidedAt($this->days(-245));
        $this->settleRemainingOptions(
            $declined,
            DateOptionStatus::Declined,
        );
        $manager->persist($declined);

        $manager->flush();

        $this->applyBackdating($manager);

        foreach (
            [
                self::REFERENCE_PROPOSAL_CONTESTED_FIRST => $first,
                self::REFERENCE_PROPOSAL_CONTESTED_SECOND => $second,
                self::REFERENCE_PROPOSAL_CONTESTED_THIRD => $third,
                self::REFERENCE_PROPOSAL_SECOND_OF_TWO => $fourth,
                self::REFERENCE_PROPOSAL_SCHEDULED => $scheduled,
                self::REFERENCE_PROPOSAL_CLEARED_BUDGET => $clearedBudget,
                self::REFERENCE_PROPOSAL_CLEARED_FREE => $clearedFree,
                self::REFERENCE_PROPOSAL_LAPSED => $lapsed,
                self::REFERENCE_PROPOSAL_WITHDRAWN => $withdrawn,
                self::REFERENCE_PROPOSAL_DECLINED => $declined,
            ] as $reference => $proposal
        ) {
            $this->addReference(
                $reference,
                $proposal,
            );
        }
    }

    /**
     * @return array<array-key, class-string<Fixture>>
     */
    #[Override]
    public function getDependencies(): array
    {
        return [
            MemberFixture::class,
            // Proposals name the body hosting the activity, so the organs must be seeded first.
            DecisionFixture::class,
            // A reserved date starts an activity off, and the tests that reach for a seeded activity by its id expect
            // the ordinary ones to be numbered first, so this has to seed after them.
            ActivityFixture::class,
        ];
    }

    private function period(
        string $name,
        int $opensInDays,
        int $closesInDays,
        int $startsInDays,
        int $endsInDays,
    ): OptionPeriod {
        $period = new OptionPeriod();
        $period->setName($name);
        $period->setSubmissionOpensAt($this->days($opensInDays));
        $period->setSubmissionClosesAt($this->days($closesInDays));
        $period->setStartsAt($this->days($startsInDays));
        $period->setEndsAt($this->days($endsInDays));

        return $period;
    }

    private function proposal(
        OptionPeriod $period,
        ?Organ $organ,
        Member $createdBy,
        string $name,
        ?string $description,
        int $createdDaysAgo,
    ): ActivityProposal {
        $proposal = new ActivityProposal();
        $proposal->setPeriod($period);
        $proposal->setOrgan($organ);
        $proposal->setCreatedBy($createdBy);
        $proposal->setName($name);
        $proposal->setDescription($description);

        $this->backdated[] = [
            $proposal,
            $this->days($createdDaysAgo),
        ];

        return $proposal;
    }

    private function option(
        ActivityProposal $proposal,
        int $position,
        DateTime $beginsAt,
        DateTime $endsAt,
        TimeOfDay $timeOfDay,
    ): ActivityDateOption {
        $option = new ActivityDateOption();
        $option->setPosition($position);
        $option->setBeginsAt($beginsAt);
        $option->setEndsAt($endsAt);
        $option->setTimeOfDay($timeOfDay);
        $proposal->addDateOption($option);

        return $option;
    }

    private function reserve(
        ActivityProposal $proposal,
        ActivityDateOption $option,
        Member $decidedBy,
    ): void {
        $option->setStatus(DateOptionStatus::Approved);
        $option->setDecidedBy($decidedBy);
        $option->setDecidedAt($this->days(-40));

        $proposal->declineDateOptionsOtherThan($option);
        $proposal->setChosenOption($option);
        $proposal->setStatus(ProposalStatus::Scheduled);
        $proposal->setDecidedBy($decidedBy);
        $proposal->setDecidedAt($this->days(-40));
    }

    private function clear(
        ActivityProposal $proposal,
        BudgetClearance $clearance,
        Member $clearedBy,
    ): void {
        $proposal->setStatus(ProposalStatus::Cleared);
        $proposal->setBudgetClearance($clearance);
        $proposal->setBudgetClearedBy($clearedBy);
        $proposal->setBudgetClearedAt($this->days(-35));
    }

    private function settleRemainingOptions(
        ActivityProposal $proposal,
        DateOptionStatus $status,
    ): void {
        foreach ($proposal->getDateOptions() as $option) {
            $option->setStatus($status);
        }
    }

    /**
     * The activity a reserved date starts off, exactly as the option calendar creates it: the body, the working title
     * and the days filled in, everything else still to be written.
     */
    private function draftActivity(
        ObjectManager $manager,
        Member $creator,
        Organ $organ,
        string $name,
        ActivityDateOption $option,
    ): Activity {
        $activity = new Activity();
        $activity->setCreator($creator);

        $revision = new ActivityRevision();
        $revision->setAuthor($creator);
        $revision->setOrgan($organ);
        $revision->setName(new ActivityLocalisedText($name, $name));
        $revision->setLocation(new ActivityLocalisedText());
        $revision->setCosts(new ActivityLocalisedText());
        $revision->setDescription(new ActivityLocalisedText());
        $revision->setCategory(ActivityCategories::Other);
        $revision->setBeginTime(new DateTime(sprintf('%s 00:00:00', $option->getBeginsAt()->format('Y-m-d'))));
        $revision->setEndTime(new DateTime(sprintf('%s 23:59:59', $option->getEndsAt()->format('Y-m-d'))));

        $activity->addRevision($revision);
        $activity->setCurrentRevision($revision);

        $manager->persist($activity);
        $manager->persist($revision);

        return $activity;
    }

    private function applyBackdating(ObjectManager $manager): void
    {
        if (!$manager instanceof EntityManagerInterface) {
            throw new RuntimeException('Backdating the proposals needs the Doctrine ORM entity manager.');
        }

        $query = $manager->createQuery(
            'UPDATE ' . ActivityProposal::class . ' p SET p.createdAt = :createdAt WHERE p.id = :id',
        );

        foreach ($this->backdated as [$proposal, $createdAt]) {
            $query->setParameter(
                'createdAt',
                $createdAt,
            )
                ->setParameter(
                    'id',
                    $proposal->getId(),
                )
                ->execute();
        }
    }

    private function days(int $offset): DateTime
    {
        return new DateTime(sprintf('%+d days', $offset));
    }
}
