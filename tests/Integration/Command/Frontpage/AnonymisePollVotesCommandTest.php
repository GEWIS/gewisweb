<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command\Frontpage;

use App\Entity\Frontpage\Poll;
use App\Entity\Frontpage\PollCommentReaction;
use App\Entity\Frontpage\PollVote;
use App\Repository\Frontpage\PollRepository;
use App\Tests\Integration\DatabaseTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use function count;
use function intval;

/**
 * A month after a poll has closed, how it was answered stops being anybody's in particular. These pin that what the
 * association answered survives that: the tallies are identical before and after, while the rows saying who answered
 * are gone and the reactions no longer name anybody.
 */
final class AnonymisePollVotesCommandTest extends DatabaseTestCase
{
    public function testTheTalliesSurviveTheirVotesBeingRemoved(): void
    {
        $poll = $this->aClosedPoll();
        $before = $poll->getTotalVotesCount();
        self::assertGreaterThan(
            0,
            $this->voteRowsFor($poll),
        );

        $this->anonymise();

        self::assertSame(
            $before,
            $this->reread($poll)->getTotalVotesCount(),
        );
        self::assertSame(
            0,
            $this->voteRowsFor($poll),
        );
    }

    public function testTheReactionsKeepTheirCountsButLoseTheirMembers(): void
    {
        $poll = $this->aClosedPoll();
        $before = count($this->reactionsFor($poll));
        self::assertGreaterThan(
            0,
            $before,
        );

        $this->anonymise();

        $after = $this->reactionsFor($this->reread($poll));
        self::assertCount(
            $before,
            $after,
        );

        foreach ($after as $reaction) {
            self::assertNull($reaction->getMember());
        }
    }

    public function testAPollIsStampedAndNotAnonymisedTwice(): void
    {
        $poll = $this->aClosedPoll();

        $this->anonymise();
        $stamped = $this->reread($poll)->getVotesAnonymisedAt();
        self::assertNotNull($stamped);
        $total = $this->reread($poll)->getTotalVotesCount();

        $output = $this->anonymise();

        self::assertStringContainsString(
            'across 0 poll(s)',
            $output,
        );
        self::assertEquals(
            $stamped,
            $this->reread($poll)->getVotesAnonymisedAt(),
        );
        self::assertSame(
            $total,
            $this->reread($poll)->getTotalVotesCount(),
        );
    }

    /**
     * A poll that is still running is left alone: a member can still be told what they answered until well after it
     * has closed.
     */
    public function testARunningPollIsLeftAlone(): void
    {
        $running = self::getContainer()->get(PollRepository::class)->findCurrentPoll();
        self::assertInstanceOf(
            Poll::class,
            $running,
        );
        $before = $this->voteRowsFor($running);

        $this->anonymise();

        self::assertNull($this->reread($running)->getVotesAnonymisedAt());
        self::assertSame(
            $before,
            $this->voteRowsFor($running),
        );
    }

    private function anonymise(): string
    {
        $tester = new CommandTester(new Application(self::bootKernel())->find('app:poll:anonymise-votes'));
        $tester->execute([]);

        return $tester->getDisplay();
    }

    private function aClosedPoll(): Poll
    {
        foreach (self::getContainer()->get(PollRepository::class)->findAll() as $poll) {
            if (
                null === $poll->getLiveRevision()
                || $poll->isActive()
                || null !== $poll->getVotesAnonymisedAt()
            ) {
                continue;
            }

            return $poll;
        }

        self::fail('The seed is expected to contain a poll that has closed and not yet been anonymised.');
    }

    private function reread(Poll $poll): Poll
    {
        $id = $poll->getId();
        $this->entityManager->clear();

        $fresh = $this->entityManager->find(
            Poll::class,
            $id,
        );
        self::assertInstanceOf(
            Poll::class,
            $fresh,
        );

        return $fresh;
    }

    /**
     * @return PollCommentReaction[]
     */
    private function reactionsFor(Poll $poll): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('r')
            ->from(
                PollCommentReaction::class,
                'r',
            )
            ->join(
                'r.comment',
                'c',
            )
            ->where('IDENTITY(c.poll) = :poll')
            ->setParameter(
                'poll',
                $poll->getId(),
            )
            ->getQuery()
            ->getResult();
    }

    private function voteRowsFor(Poll $poll): int
    {
        return intval($this->entityManager->createQueryBuilder()
            ->select('COUNT(v.respondent)')
            ->from(
                PollVote::class,
                'v',
            )
            ->where('v.poll = :poll')
            ->setParameter(
                'poll',
                $poll->getId(),
            )
            ->getQuery()
            ->getSingleScalarResult());
    }
}
