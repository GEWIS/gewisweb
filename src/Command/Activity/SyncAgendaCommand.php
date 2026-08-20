<?php

declare(strict_types=1);

namespace App\Command\Activity;

use App\Service\Activity\AgendaFeed;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

use function sprintf;

/**
 * Keeps a copy of the association's own agenda, so the option calendar can show the days that are already taken by
 * something the website does not hold itself.
 *
 * This is the only place that agenda is ever fetched. A page render reads the copy and nothing else, so a reader
 * never waits on somebody else's server, and an agenda that is down costs the calendar a layer rather than a page.
 *
 * A failed run says so and changes nothing: the copy that is already kept is a great deal more use than none, and
 * the next run is a quarter of an hour away.
 */
#[AsCommand(
    name: 'app:activity:sync-agenda',
    description: 'Fetch the association agenda the option calendar draws behind the days bodies ask for.',
)]
#[AsCronTask(
    expression: '*/15 * * * *',
    jitter: 60,
)]
final class SyncAgendaCommand extends Command
{
    public function __construct(
        private readonly AgendaFeed $agendaFeed,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
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

        if (!$this->agendaFeed->isConfigured()) {
            $ui->note('No agenda is configured, so there is nothing to fetch.');

            return Command::SUCCESS;
        }

        $fetched = $this->agendaFeed->refresh();

        if (null === $fetched) {
            $this->logger->warning('The association agenda could not be fetched; the last copy is kept.');
            $ui->warning('The agenda could not be fetched. The copy that was already kept is left alone.');

            return Command::SUCCESS;
        }

        $ui->success(sprintf(
            'Kept %d agenda item(s).',
            $fetched,
        ));

        return Command::SUCCESS;
    }
}
