<?php

declare(strict_types=1);

namespace App\Command\Activity;

use App\Entity\Activity\ActivityProposal;
use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Application\Enums\NotificationType;
use App\Repository\Activity\ActivityProposalRepository;
use App\Repository\User\UserRepository;
use App\Service\Activity\OptionBudgetSchedule;
use App\Service\Application\NotificationPublisher;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;
use Symfony\Component\Workflow\WorkflowInterface;

use function count;
use function sprintf;
use function strval;

/**
 * Releases a day whose holder never settled the financial side, so whoever is next in line can have it.
 *
 * The rule the paper calendar had: a body that holds a day and does not get its budget in loses the day, rather than
 * sitting on it until it is too late for anybody else. The old site chased this with an email to the web committee and
 * left the releasing to a human, which is why the calendar filled up with claims nobody was going to use.
 *
 * A proposal the board has settled either way is never touched, and that includes one settled by the board saying
 * there is no budget to approve, because an activity that costs nothing has nothing to hand in.
 */
#[AsCommand(
    name: 'app:activity:lapse-overdue-options',
    description: 'Release reserved days whose budget was never settled.',
)]
#[AsCronTask(
    expression: '35 8 * * *',
    jitter: 600,
)]
final class LapseOverdueOptionsCommand extends Command
{
    public function __construct(
        private readonly ActivityProposalRepository $activityProposalRepository,
        private readonly UserRepository $userRepository,
        private readonly NotificationPublisher $publisher,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly OptionBudgetSchedule $schedule,
        private readonly WorkflowInterface $activityProposalStateMachine,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Report which days would be released without releasing them.',
        );
    }

    #[Override]
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $ui = new SymfonyStyle(
            $input,
            $output,
        );
        $dryRun = true === $input->getOption('dry-run');

        $proposals = $this->activityProposalRepository->findDueToLapse($this->schedule->lapseBefore());

        if ([] === $proposals) {
            $ui->success('No reserved day has run out of road.');

            return Command::SUCCESS;
        }

        $released = 0;
        foreach ($proposals as $proposal) {
            $ui->text(sprintf(
                '%s (%s) on %s',
                $proposal->getName(),
                $proposal->getOrgan()?->getAbbr() ?? 'the board',
                $proposal->getChosenOption()?->getBeginsAt()->format('Y-m-d') ?? '?',
            ));

            if ($dryRun) {
                continue;
            }

            // Nothing should refuse it at this point, but a domain guard added later might, and a sweep that stops
            // dead on one row would leave the rest of the calendar clogged. Skip it and say so.
            if (
                !$this->activityProposalStateMachine->can(
                    $proposal,
                    'lapse',
                )
            ) {
                $this->logger->warning(
                    'A reserved day could not be released.',
                    ['proposal' => $proposal->getId()],
                );

                continue;
            }

            $this->activityProposalStateMachine->apply(
                $proposal,
                'lapse',
            );
            $this->tell($proposal);
            ++$released;
        }

        if ($dryRun) {
            $ui->success(sprintf(
                '%d reserved day(s) would be released.',
                count($proposals),
            ));

            return Command::SUCCESS;
        }

        $this->entityManager->flush();

        $ui->success(sprintf(
            'Released %d reserved day(s).',
            $released,
        ));

        return Command::SUCCESS;
    }

    private function tell(ActivityProposal $proposal): void
    {
        $proposalId = $proposal->getId();

        if (null === $proposalId) {
            return;
        }

        $user = $this->userRepository->find($proposal->getCreatedBy()->getLidnr());

        if (null === $user) {
            return;
        }

        $this->publisher->publishFor(
            $user,
            NotificationType::ActivityProposalLapsed,
            [
                'proposal' => strval($proposalId),
                'proposalName' => $proposal->getName(),
            ],
            AlertTypes::Warning,
        );
    }
}
