<?php

declare(strict_types=1);

namespace App\Command\User;

use App\Repository\User\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

use function sprintf;

#[AsCommand(
    name: 'app:user:purge-expired-sessions',
    description: 'Remove expired remember-me sessions from the database.',
)]
#[AsCronTask(
    expression: '30 3 * * *',
    schedule: 'gdpr',
)]
final class PurgeExpiredSessionsCommand extends Command
{
    public function __construct(
        private readonly SessionRepository $repository,
        private readonly EntityManagerInterface $em,
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

        $count = $this->repository->deleteExpired();
        $this->em->flush();

        $io->success(sprintf('Purged %d expired session%s.', $count, 1 !== $count ? 's' : ''));

        return Command::SUCCESS;
    }
}
