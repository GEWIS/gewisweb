<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Activity;

use App\Entity\Activity\ActivityProposal;
use App\Entity\Activity\Enums\ProposalLimitSource;
use App\Entity\Activity\OptionPeriod;
use App\Entity\Activity\PeriodProposalLimit;
use App\Entity\Activity\ProposalLimit;
use App\Entity\Decision\Organ;
use App\Service\Activity\ProposalLimitResolver;
use App\Tests\Integration\DatabaseTestCase;
use DateTime;

use function count;

/**
 * The ladder that answers how many activities a body may put forward, against the seeded calendar.
 *
 * The seed holds GETÉST to two in the round that is open (a one-off override) and has it use both, puts KEUR on a
 * standing limit of two with one used, and writes nothing at all for any other body. That last case is the one the old
 * calendar got wrong: it wrote a row per body when a period was opened, started them all at zero, and read a missing
 * row as zero as well, so a body founded afterwards could propose nothing.
 */
final class ProposalLimitResolverTest extends DatabaseTestCase
{
    public function testABodyWithNoLimitRowsAnywhereGetsTheAssociationDefault(): void
    {
        $allowance = $this->resolver()->allowanceFor(
            $this->bodyWithoutAnyLimit(),
            $this->openPeriod(),
        );

        self::assertSame(
            3,
            $allowance->maximum,
        );
        self::assertSame(
            0,
            $allowance->used,
        );
        self::assertSame(
            3,
            $allowance->remaining(),
        );
        self::assertSame(
            ProposalLimitSource::GlobalDefault,
            $allowance->source,
        );
        self::assertFalse($allowance->isExhausted());
    }

    /**
     * The bug this redesign exists for. Opening a period writes nothing per body, so every body has an allowance in a
     * period that has only just been created, including one the board has never heard of.
     */
    public function testOpeningAPeriodGivesEveryBodyAnAllowanceWithoutWritingAnything(): void
    {
        $period = new OptionPeriod();
        $period->setName('A round nobody has been set up for');
        $period->setSubmissionOpensAt(new DateTime('-1 day'));
        $period->setSubmissionClosesAt(new DateTime('+30 days'));
        $period->setStartsAt(new DateTime('+200 days'));
        $period->setEndsAt(new DateTime('+290 days'));

        $this->entityManager->persist($period);
        $this->entityManager->flush();

        $organs = $this->allBodies();
        $allowances = $this->resolver()->allowancesFor(
            $organs,
            $period,
        );

        self::assertCount(
            count($organs),
            $allowances,
        );

        foreach ($allowances as $allowance) {
            self::assertGreaterThan(
                0,
                $allowance->maximum,
                'No body may be shut out of a period by nobody having written a row for it.',
            );
            self::assertSame(
                0,
                $allowance->used,
            );
        }

        self::assertCount(
            0,
            $this->entityManager->getRepository(PeriodProposalLimit::class)->findBy(['period' => $period]),
            'Opening a period must not write a limit row for anybody.',
        );
    }

    public function testAStandingOverrideBeatsTheAssociationDefault(): void
    {
        $allowance = $this->resolver()->allowanceFor(
            $this->body('KEUR'),
            $this->openPeriod(),
        );

        self::assertSame(
            2,
            $allowance->maximum,
        );
        self::assertSame(
            ProposalLimitSource::StandingOverride,
            $allowance->source,
        );
        self::assertSame(
            1,
            $allowance->used,
        );
        self::assertSame(
            1,
            $allowance->remaining(),
        );
    }

    public function testAPeriodOverrideBeatsAStandingOne(): void
    {
        $period = $this->openPeriod();
        $keur = $this->body('KEUR');

        $override = new PeriodProposalLimit();
        $override->setPeriod($period);
        $override->setOrgan($keur);
        $override->setMaxProposals(5);

        $this->entityManager->persist($override);
        $this->entityManager->flush();

        $allowance = $this->resolver()->allowanceFor(
            $keur,
            $period,
        );

        self::assertSame(
            5,
            $allowance->maximum,
        );
        self::assertSame(
            ProposalLimitSource::PeriodOverride,
            $allowance->source,
        );
    }

    public function testThePeriodDefaultAnswersWhenNothingIsSetForTheBody(): void
    {
        $period = $this->openPeriod();
        $period->setDefaultMaxProposals(1);
        $this->entityManager->flush();

        $allowance = $this->resolver()->allowanceFor(
            $this->bodyWithoutAnyLimit(),
            $period,
        );

        self::assertSame(
            1,
            $allowance->maximum,
        );
        self::assertSame(
            ProposalLimitSource::PeriodDefault,
            $allowance->source,
        );
    }

    public function testABodyOnItsLimitIsExhausted(): void
    {
        $allowance = $this->resolver()->allowanceFor(
            $this->body('GETÉST'),
            $this->openPeriod(),
        );

        self::assertSame(
            2,
            $allowance->maximum,
        );
        self::assertSame(
            2,
            $allowance->used,
        );
        self::assertTrue($allowance->isExhausted());
        self::assertSame(
            ProposalLimitSource::PeriodOverride,
            $allowance->source,
        );
    }

    /**
     * Editing must not count the proposal being edited, or a body on its last slot could never save a change to it.
     */
    public function testTheProposalBeingEditedIsLeftOutOfTheCount(): void
    {
        $period = $this->openPeriod();
        $getest = $this->body('GETÉST');

        $proposals = $this->entityManager->getRepository(ActivityProposal::class)->findForOrgansInPeriod(
            $period,
            [$getest],
        );

        $allowance = $this->resolver()->allowanceFor(
            $getest,
            $period,
            $proposals[0],
        );

        self::assertSame(
            1,
            $allowance->used,
        );
        self::assertFalse($allowance->isExhausted());
    }

    /**
     * The batch answer has to say exactly what the one-at-a-time answer says, or the picker and the form disagree.
     */
    public function testTheBatchAnswerMatchesTheSingleAnswer(): void
    {
        $period = $this->openPeriod();
        $organs = [
            $this->body('GETÉST'),
            $this->body('KEUR'),
            $this->bodyWithoutAnyLimit(),
        ];

        $resolver = $this->resolver();
        $batch = $resolver->allowancesFor(
            $organs,
            $period,
        );

        foreach ($organs as $organ) {
            $organId = $organ->getId();
            self::assertNotNull($organId);

            $single = $resolver->allowanceFor(
                $organ,
                $period,
            );

            self::assertSame(
                $single->maximum,
                $batch[$organId]->maximum,
            );
            self::assertSame(
                $single->used,
                $batch[$organId]->used,
            );
            self::assertSame(
                $single->source,
                $batch[$organId]->source,
            );
        }
    }

    /**
     * Built by hand rather than pulled out of the container: until a controller injects it the compiler inlines it
     * away, and the ladder is worth testing before there is a screen on top of it.
     */
    private function resolver(): ProposalLimitResolver
    {
        return new ProposalLimitResolver(
            $this->entityManager->getRepository(PeriodProposalLimit::class),
            $this->entityManager->getRepository(ProposalLimit::class),
            $this->entityManager->getRepository(ActivityProposal::class),
            self::getContainer()->getParameter('app.activity.default_max_proposals'),
        );
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

    /**
     * @return Organ[]
     */
    private function allBodies(): array
    {
        return $this->entityManager->getRepository(Organ::class)->findAll();
    }

    /**
     * A seeded body that no limit row mentions, which is the ordinary case the calendar has to get right.
     */
    private function bodyWithoutAnyLimit(): Organ
    {
        foreach ($this->allBodies() as $organ) {
            if (
                null !== $this->entityManager->getRepository(ProposalLimit::class)->findOneBy(['organ' => $organ])
                || null !== $this->entityManager->getRepository(PeriodProposalLimit::class)
                    ->findOneBy(['organ' => $organ])
            ) {
                continue;
            }

            return $organ;
        }

        self::fail('The seed is expected to hold a body that no limit row mentions.');
    }
}
