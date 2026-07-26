<?php

declare(strict_types=1);

namespace App\Command\Application;

use App\Service\Application\RealtimeNotifier;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:maintenance:notify',
    description: 'Tell every connected client to reload, e.g. right before maintenance.',
)]
final class MaintenanceNotifyCommand extends Command
{
    public function __construct(private readonly RealtimeNotifier $realtimeNotifier)
    {
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

        $this->realtimeNotifier->reloadPublic();
        $io->success('Asked every connected client to reload.');

        return Command::SUCCESS;
    }
}
