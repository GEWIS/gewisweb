<?php

declare(strict_types=1);

namespace App\Command\Activity;

use App\Repository\Activity\SignupFieldValueRepository;
use App\Repository\Activity\SignupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

use function sprintf;

#[AsCommand(
    name: 'app:activity:delete-old-signups',
    description: 'Delete sign-ups for activities older than 5 years.',
)]
#[AsCronTask(
    expression: '0 3 * * *',
    schedule: 'gdpr',
)]
final class DeleteOldSignupsCommand extends Command
{
    public function __construct(
        private readonly SignupRepository $signupRepository,
        private readonly SignupFieldValueRepository $signupFieldValueRepository,
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
        $removedSignups = 0;
        $removedFieldValues = 0;

        $this->logger->info('Starting cleanup of sign-ups older than 5 years.');

        foreach ($this->signupRepository->getSignupsOlderThan5Years() as $signup) {
            foreach ($this->signupFieldValueRepository->getFieldValuesBySignup($signup) as $fieldValue) {
                $this->entityManager->remove($fieldValue);
                ++$removedFieldValues;
            }

            $this->entityManager->remove($signup);
            ++$removedSignups;
        }

        $this->entityManager->flush();

        $message = sprintf(
            'Deleted %d sign-up(s) and %d related field value(s).',
            $removedSignups,
            $removedFieldValues,
        );

        $this->logger->info($message);
        $io->success($message);

        return Command::SUCCESS;
    }
}
