<?php

declare(strict_types=1);

namespace App\Command\Photo;

use App\Service\Photo\WeeklyPhotoService;
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
 * Choose and store the photo of the week from the past week's votes.
 *
 * Runs Monday before the board's hide window (00:00-11:59): the freshly chosen photo must exist before then so the
 * board can decide whether to hide it. The scheduler is stateful with processOnlyLastMissedRun, so downtime yields a
 * single catch-up run rather than a backlog. No votes this week is not a failure; the command reports it and exits 0.
 */
#[AsCommand(
    name: 'app:photo:weekly',
    description: 'Choose and store the photo of the week from the past week\'s votes.',
)]
#[AsCronTask(expression: '0 3 * * 1')]
final class GenerateWeeklyPhotoCommand extends Command
{
    public function __construct(
        private readonly WeeklyPhotoService $weeklyPhotoService,
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

        $weeklyPhoto = $this->weeklyPhotoService->generatePhotoOfTheWeek();

        if (null === $weeklyPhoto) {
            $io->warning('No photo of the week was chosen; no photos were voted on this week.');

            return Command::SUCCESS;
        }

        $message = sprintf(
            'Photo of the week set to photo %d.',
            $weeklyPhoto->getPhoto()->getId() ?? 0,
        );
        $this->logger->info($message);
        $io->success($message);

        return Command::SUCCESS;
    }
}
