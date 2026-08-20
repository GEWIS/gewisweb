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
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

use function count;
use function sprintf;
use function strval;

/**
 * Warn a body that the day it is holding is at risk, while it can still do something about it.
 *
 * Association policy is that an organiser may not commit, spend or promote before their budget has been approved at a
 * board meeting, and that the budget has to be in hand early enough for a meeting well before the activity. The
 * website cannot work that deadline out, because board meetings are not known in advance; what it can see is whether
 * the board has recorded an outcome, and that is what this chases. An activity that costs nothing is settled by the
 * board saying so, and is never chased.
 *
 * Once per proposal: a nightly nag is noise, and a body that has been told knows.
 */
#[AsCommand(
    name: 'app:activity:remind-option-budget',
    description: 'Warn bodies whose reserved day is at risk because nothing has been recorded about its budget.',
)]
#[AsCronTask(
    expression: '25 8 * * *',
    jitter: 600,
)]
final class RemindOptionBudgetCommand extends Command
{
    public function __construct(
        private readonly ActivityProposalRepository $activityProposalRepository,
        private readonly UserRepository $userRepository,
        private readonly NotificationPublisher $publisher,
        private readonly EntityManagerInterface $entityManager,
        private readonly OptionBudgetSchedule $schedule,
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
            'Report who would be warned without telling anybody.',
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

        $proposals = $this->activityProposalRepository->findNeedingBudgetReminder($this->schedule->remindBefore());

        if ([] === $proposals) {
            $ui->success('No reserved day is at risk.');

            return Command::SUCCESS;
        }

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

            $this->warn($proposal);
        }

        if ($dryRun) {
            $ui->success(sprintf(
                '%d reserved day(s) would be reported as at risk.',
                count($proposals),
            ));

            return Command::SUCCESS;
        }

        $this->entityManager->flush();

        $ui->success(sprintf(
            'Warned about %d reserved day(s).',
            count($proposals),
        ));

        return Command::SUCCESS;
    }

    /**
     * The member who handed the proposal in is the one told. Notifications reach an account or a role, never a body,
     * and that member is the one who knows what became of the budget.
     */
    private function warn(ActivityProposal $proposal): void
    {
        $proposalId = $proposal->getId();

        if (null === $proposalId) {
            return;
        }

        $user = $this->userRepository->find($proposal->getCreatedBy()->getLidnr());

        // A body whose member no longer has an account still gets its day released on time; there is simply nobody to
        // warn first, so the stamp is set anyway rather than looking again every night.
        $proposal->setBudgetRemindedAt(new DateTime());

        if (null === $user) {
            return;
        }

        $this->publisher->publishFor(
            $user,
            NotificationType::ActivityProposalBudgetDue,
            [
                'proposal' => strval($proposalId),
                'proposalName' => $proposal->getName(),
            ],
            AlertTypes::Warning,
        );
    }
}
