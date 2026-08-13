<?php

declare(strict_types=1);

namespace App\Command\Frontpage;

use App\Entity\Frontpage\Poll;
use App\Repository\Frontpage\PollCommentReactionRepository;
use App\Repository\Frontpage\PollRepository;
use App\Repository\Frontpage\PollVoteRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

use function intval;
use function sprintf;

/**
 * A month after a poll has closed, how it was answered stops being anybody's in particular: each option keeps its
 * tally and the rows saying who gave which answer are removed, along with the members behind the reactions underneath
 * it. What the poll asked, and what the association answered, survives all of it.
 *
 * Daily on the GDPR schedule is what "exactly one month" means here: the run on the day the month elapses picks the
 * poll up.
 */
#[AsCommand(
    name: 'app:poll:anonymise-votes',
    description: 'Turn the votes on polls that closed a month ago into anonymous tallies.',
)]
#[AsCronTask(
    expression: '30 4 * * *',
    jitter: 900,
    schedule: 'gdpr',
)]
final class AnonymisePollVotesCommand extends Command
{
    public function __construct(
        private readonly PollRepository $pollRepository,
        private readonly PollVoteRepository $voteRepository,
        private readonly PollCommentReactionRepository $reactionRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle(
            $input,
            $output,
        );

        $this->logger->info('Starting anonymisation of votes on polls that closed a month ago.');

        $polls = 0;
        $votes = 0;
        $reactions = 0;

        foreach ($this->pollRepository->findDueForVoteAnonymisation() as $poll) {
            ++$polls;

            // A poll at a time, so a run that stops half way leaves every poll it did reach with its tallies, its
            // votes gone and its date set, rather than a backlog of tallies that were never paid for.
            $this->entityManager->wrapInTransaction(
                function () use ($poll, &$votes, &$reactions): void {
                    $votes += $this->anonymiseVotes($poll);
                    $reactions += $this->reactionRepository->anonymiseForPoll($poll);

                    $poll->setVotesAnonymisedAt(new DateTime());
                    $this->entityManager->flush();
                },
            );
        }

        $message = sprintf(
            'Anonymised %d vote(s) and %d reaction(s) across %d poll(s).',
            $votes,
            $reactions,
            $polls,
        );

        $this->logger->info($message);
        $io->success($message);

        return Command::SUCCESS;
    }

    /**
     * Counted into the tallies first and then removed wholesale: the votes on a poll the association answered are a
     * row per member, and none of them has to be loaded to be counted or to go.
     */
    private function anonymiseVotes(Poll $poll): int
    {
        $counts = $this->voteRepository->countsForPoll($poll);

        foreach ($poll->getOptions() as $option) {
            $option->setAnonymousVotes(
                $option->getAnonymousVotes() + ($counts[intval($option->getId())] ?? 0),
            );
        }

        return $this->voteRepository->deleteForPoll($poll);
    }
}
