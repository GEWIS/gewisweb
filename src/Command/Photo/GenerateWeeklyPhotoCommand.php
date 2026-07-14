<?php

declare(strict_types=1);

namespace App\Command\Photo;

use App\Entity\Photo\WeeklyPhoto;
use App\Repository\Photo\PhotoRepository;
use App\Service\Photo\WeeklyPhotoService;
use DateTime;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;
use Throwable;

use function is_string;
use function sprintf;

/**
 * Choose and store the photo of the week from a week's votes.
 *
 * Runs Monday before the board's hide window (00:00-11:59): the freshly chosen photo must exist before then so the
 * board can decide whether to hide it. The scheduler is stateful with processOnlyLastMissedRun, so downtime yields a
 * single catch-up run rather than a backlog. No votes that week is not a failure; the command reports it and exits 0.
 *
 * An admin can pass --week to (re)generate for another week, and --photo to pick a specific photo by hand instead of
 * the vote-based selection.
 */
#[AsCommand(
    name: 'app:photo:weekly',
    description: 'Choose and store the photo of the week from a week\'s votes.',
)]
#[AsCronTask(expression: '0 3 * * 1')]
final class GenerateWeeklyPhotoCommand extends Command
{
    public function __construct(
        private readonly WeeklyPhotoService $weeklyPhotoService,
        private readonly PhotoRepository $photoRepository,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->addOption(
            'week',
            null,
            InputOption::VALUE_REQUIRED,
            'Start date of the week to (re)generate for (e.g. 2026-07-06); defaults to a week ago.',
        );
        $this->addOption(
            'photo',
            null,
            InputOption::VALUE_REQUIRED,
            'Force a specific photo id as the photo of the week, instead of the vote-based pick.',
        );
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

        $weekOption = $input->getOption('week');
        $weekStart = null;
        if (is_string($weekOption)) {
            try {
                $weekStart = new DateTime($weekOption);
            } catch (Throwable) {
                $io->error(sprintf('Could not parse the week start date "%s".', $weekOption));

                return Command::INVALID;
            }
        }

        $weeklyPhoto = $this->choosePhoto(
            $input,
            $io,
            $weekStart,
        );
        if (false === $weeklyPhoto) {
            return Command::INVALID;
        }

        if (null === $weeklyPhoto) {
            $io->warning('No photo of the week was chosen; no photos were voted on that week.');

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

    /**
     * The chosen photo of the week: the forced photo when --photo is given (false on a bad id), otherwise the
     * vote-based pick (null when nothing was voted on).
     */
    private function choosePhoto(
        InputInterface $input,
        SymfonyStyle $io,
        ?DateTime $weekStart,
    ): WeeklyPhoto|false|null {
        $photoOption = $input->getOption('photo');
        if (!is_string($photoOption)) {
            return $this->weeklyPhotoService->generatePhotoOfTheWeek($weekStart);
        }

        $photo = $this->photoRepository->find((int) $photoOption);
        if (null === $photo) {
            $io->error(sprintf('No photo with id "%s".', $photoOption));

            return false;
        }

        return $this->weeklyPhotoService->setPhotoOfTheWeek(
            $photo,
            $weekStart,
        );
    }
}
