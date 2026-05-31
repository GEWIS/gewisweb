<?php

declare(strict_types=1);

namespace App\Command\Activity;

use App\Entity\Activity\Activity;
use App\Repository\Activity\ActivityRevisionRepository;
use App\Service\Activity\DraftDiscarder;
use App\Service\Application\EditLockService;
use DateTime;
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

use function sprintf;

/**
 * Removes abandoned activity drafts. A Draft revision that is still the working head of its activity and has not been
 * touched for {@see self::STALE_AFTER_DAYS} days is considered abandoned:
 *  - if the activity already has a live (approved) revision, only the stale draft is discarded and the activity falls
 *    back to its live version (an abandoned re-edit);
 *  - if the activity was never approved, the whole activity is removed.
 *
 * Submitted / in-review revisions (which are with the board) are never touched, and an activity whose sign-up lists
 * carry sign-ups is never deleted.
 */
#[AsCommand(
    name: 'app:activity:delete-stale-drafts',
    description: 'Delete activity drafts that have been abandoned for a long time.',
)]
#[AsCronTask(
    expression: '15 3 * * *',
    schedule: 'gdpr',
)]
final class DeleteStaleDraftsCommand extends Command
{
    private const int STALE_AFTER_DAYS = 30;

    public function __construct(
        private readonly ActivityRevisionRepository $activityRevisionRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly DraftDiscarder $draftDiscarder,
        private readonly EditLockService $editLockService,
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
            'Report what would be removed without changing anything.',
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
        $dryRun = true === $input->getOption('dry-run');
        $cutoff = new DateTime(sprintf('-%d days', self::STALE_AFTER_DAYS));

        $reverted = 0;
        $deleted = 0;
        $skipped = 0;

        $this->logger->info(sprintf(
            'Cleaning up activity drafts untouched since %s.%s',
            $cutoff->format('Y-m-d'),
            $dryRun ? ' (dry-run)' : '',
        ));

        foreach ($this->activityRevisionRepository->findStaleDraftHeads($cutoff) as $revision) {
            $activity = $revision->getActivity();

            if (null !== $activity->getLiveRevision()) {
                if (!$dryRun) {
                    $this->draftDiscarder->discardToLive($revision);
                }

                ++$reverted;
                $this->logger->info(sprintf(
                    'Activity #%d: discarded stale draft (revision #%d); reverted to the live version.',
                    $activity->getId(),
                    $revision->getId(),
                ));

                continue;
            }

            if ($this->hasSignUps($activity)) {
                ++$skipped;
                $this->logger->warning(sprintf(
                    'Activity #%d: stale draft kept because a sign-up list already has sign-ups.',
                    $activity->getId(),
                ));

                continue;
            }

            if (!$dryRun) {
                $this->deleteActivity($activity);
            }

            ++$deleted;
            $this->logger->info(sprintf(
                'Activity #%d: deleted entirely (never approved, abandoned draft).',
                $activity->getId(),
            ));
        }

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        $message = sprintf(
            'Reverted %d draft(s) to live, deleted %d abandoned activit%s, skipped %d.%s',
            $reverted,
            $deleted,
            1 === $deleted ? 'y' : 'ies',
            $skipped,
            $dryRun ? ' (dry-run; nothing changed)' : '',
        );
        $this->logger->info($message);
        $io->success($message);

        return Command::SUCCESS;
    }

    private function hasSignUps(Activity $activity): bool
    {
        // Sign-ups only ever live on the live revision's lists, but check every revision's lists defensively before
        // destroying anything.
        foreach ($activity->getRevisions() as $revision) {
            foreach ($revision->getSignupLists() as $signupList) {
                if ($signupList->hasSignUps()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Fully remove a never-approved activity and its dependent rows. There is deliberately no Doctrine cascade on
     * Activity::$revisions, so each revision is removed explicitly (which cascade-removes its own sign-up lists,
     * fields, options and texts); the activity -> revision and revision -> previousRevision foreign keys are nulled
     * first so the deletes are unambiguous.
     */
    private function deleteActivity(Activity $activity): void
    {
        // The edit lock (if any) has no foreign key to the activity, so drop it explicitly before the activity goes.
        $this->editLockService->purge($activity);

        $activity->setCurrentRevision(null);
        $activity->setLiveRevision(null);

        foreach ($activity->getRevisions() as $revision) {
            $revision->setPreviousRevision(null);
        }

        $this->entityManager->flush();

        foreach ($activity->getRevisions() as $revision) {
            $this->draftDiscarder->removeRevision($revision);
        }

        $this->entityManager->remove($activity);
    }
}
