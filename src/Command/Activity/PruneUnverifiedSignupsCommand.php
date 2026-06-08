<?php

declare(strict_types=1);

namespace App\Command\Activity;

use App\Repository\Activity\ExternalSignupVerificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

use function count;
use function sprintf;

/**
 * Delete external sign-ups whose double-opt-in (Verify) token has expired without being confirmed. Until confirmed an
 * external sign-up is hidden everywhere, so this just clears never-completed sign-ups (and their answers + token);
 * confirmed sign-ups, which hold a long-lived Manage token instead, are never touched.
 */
#[AsCommand(
    name: 'app:activity:prune-unverified-signups',
    description: 'Delete external sign-ups whose e-mail verification window has expired.',
)]
#[AsCronTask(
    expression: '30 3 * * *',
    schedule: 'gdpr',
)]
final class PruneUnverifiedSignupsCommand extends Command
{
    public function __construct(
        private readonly ExternalSignupVerificationRepository $verificationRepository,
        private readonly EntityManagerInterface $entityManager,
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

        $this->logger->info('Starting cleanup of unverified external sign-ups.');

        // Remove every expired-unverified sign-up in one transaction (mirroring DeleteOldSignupsCommand): removing the
        // sign-up cascades to its field values (ORM) and its Verify token (FK ON DELETE CASCADE), so a single flush
        // clears everything without a per-row round-trip.
        $signups = $this->verificationRepository->findExpiredUnverifiedSignups();
        foreach ($signups as $signup) {
            $this->entityManager->remove($signup);
        }

        $this->entityManager->flush();

        $message = sprintf(
            'Deleted %d unverified external sign-up(s).',
            count($signups),
        );

        $this->logger->info($message);
        $io->success($message);

        return Command::SUCCESS;
    }
}
