<?php

declare(strict_types=1);

namespace App\Command\User;

use App\MessageHandler\User\ExportUserDataHandler;
use App\Service\Application\FileStorage;
use DateTimeImmutable;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

use function sprintf;

#[AsCommand(
    name: 'app:user:prune-expired-data-exports',
    description: 'Remove member data exports that have passed their retention window.',
)]
#[AsCronTask(
    expression: '36 * * * *',
    schedule: 'gdpr',
)]
final class PruneExpiredDataExportsCommand extends Command
{
    public function __construct(
        private readonly FileStorage $fileStorage,
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

        $threshold = new DateTimeImmutable(
            '-' . ExportUserDataHandler::RETENTION_DAYS . ' days',
        )->getTimestamp();

        $removed = 0;
        foreach ($this->fileStorage->listFiles('gdpr-export') as $path) {
            if ($this->fileStorage->lastModified($path) >= $threshold) {
                continue;
            }

            $this->fileStorage->remove($path);
            $removed++;
        }

        $io->success(sprintf('Removed %d expired data export%s.', $removed, 1 !== $removed ? 's' : ''));

        return Command::SUCCESS;
    }
}
