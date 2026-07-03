<?php

declare(strict_types=1);

namespace App\Command\Activity;

use App\Repository\Activity\SignupListRepository;
use App\Service\Activity\DrawManager;
use DateTime;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

use function intdiv;
use function sprintf;

/**
 * Perform the automated admission draw for every sign-up list whose draw moment has passed.
 *
 * Runs every minute: once the draw locks a list its remaining places are handed out first-come-first-served, so the
 * switch should happen close to the announced moment. The scheduler is stateful with processOnlyLastMissedRun, so
 * downtime will result in a single catch-up run that draws all due lists at once.
 */
#[AsCommand(
    name: 'app:activity:run-due-draws',
    description: 'Perform the automated admission draw for sign-up lists whose draw moment has passed.',
)]
#[AsCronTask(expression: '* * * * *')]
final class RunDueDrawsCommand extends Command
{
    private const int LATE_DRAW_WARNING_MINUTES = 5;

    public function __construct(
        private readonly SignupListRepository $signupListRepository,
        private readonly DrawManager $drawManager,
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

        // The repository query is a coarse pre-filter; drawAutomatically() re-checks every guard under a row lock and
        // returns false when it declines (already drawn by a racing board member, admission window closed in between).
        $performed = 0;
        foreach ($this->signupListRepository->findDueForAutomatedDraw(new DateTime()) as $list) {
            if (!$this->drawManager->drawAutomatically($list)) {
                continue;
            }

            ++$performed;
            $this->logger->info(sprintf(
                'Automatically drew sign-up list %d of activity %d.',
                $list->getId() ?? 0,
                $list->getActivity()->getId() ?? 0,
            ));

            $dueAt = $list->getAutoDrawAt();
            if (null === $dueAt) {
                continue;
            }

            $minutesLate = intdiv(
                new DateTime()->getTimestamp() - $dueAt->getTimestamp(),
                60,
            );
            if ($minutesLate < self::LATE_DRAW_WARNING_MINUTES) {
                continue;
            }

            $this->logger->warning(sprintf(
                'The draw of sign-up list %d of activity %d ran %d minutes past its announced moment.',
                $list->getId() ?? 0,
                $list->getActivity()->getId() ?? 0,
                $minutesLate,
            ));
        }

        $io->success(sprintf(
            'Performed %d automated draw(s).',
            $performed,
        ));

        return Command::SUCCESS;
    }
}
