<?php

declare(strict_types=1);

namespace App\Command\Frontpage;

use App\Service\Application\RealtimeNotifier;
use App\Service\Frontpage\InfimumService;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

/**
 * Fetches a fresh infimum and pushes it to every member who has a page open, so the panel changes on its own rather
 * than waiting for somebody to reload.
 *
 * A failed fetch publishes nothing at all: whoever is reading keeps the infimum they already had, which is a better
 * answer than blanking the panel because somebody else's server had a bad minute.
 */
#[AsCommand(
    name: 'app:infimum:rotate',
    description: 'Fetch a fresh infimum and push it to every member with a page open.',
)]
#[AsCronTask(
    expression: '*/5 * * * *',
    jitter: 60,
)]
final class RotateInfimumCommand extends Command
{
    public function __construct(
        private readonly InfimumService $infimumService,
        private readonly RealtimeNotifier $realtimeNotifier,
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

        $infimum = $this->infimumService->refresh();

        if (null === $infimum) {
            $this->logger->info('No infimum could be fetched, so the one that is up stays up.');
            $io->note('No infimum could be fetched; nothing was published.');

            return Command::SUCCESS;
        }

        $this->realtimeNotifier->rotateInfimum($infimum);

        $io->success('A fresh infimum was published.');

        return Command::SUCCESS;
    }
}
