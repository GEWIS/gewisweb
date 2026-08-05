<?php

declare(strict_types=1);

namespace App\Command\Education;

use App\Service\Education\CourseDocumentDownloadService;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

use function sprintf;

#[AsCommand(
    name: 'app:education:prune-expired-downloads',
    description: 'Remove watermarked course documents that were built but never collected, or have gone stale.',
)]
#[AsCronTask(expression: '* * * * *')]
final class PruneExpiredDownloadsCommand extends Command
{
    public function __construct(
        private readonly CourseDocumentDownloadService $downloadService,
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

        $removed = $this->downloadService->purgeExpired();

        $io->success(sprintf('Removed %d expired download%s.', $removed, 1 !== $removed ? 's' : ''));

        return Command::SUCCESS;
    }
}
