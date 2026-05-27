<?php

declare(strict_types=1);

namespace App\Command\User;

use App\Entity\Decision\AssociationYear;
use App\Entity\User\CompanyUser;
use App\Entity\User\User;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;
use Throwable;

use function boolval;
use function sprintf;

#[AsCommand(
    name: 'app:users:force-relogin',
    description: 'Refresh the relogin timestamp for users so remember-me cookies become invalid.',
)]
#[AsCronTask('0 6 1 7 *')]
final class ForceReloginUsersCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->addOption(
            'company-users',
            null,
            InputOption::VALUE_NONE,
            'Also refresh the relogin timestamp for company users.',
        );

        $this->addArgument(
            'date',
            InputArgument::OPTIONAL,
            'The relogin date/time (defaults to 1 July of current year at 00:00)',
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
        $dateArg = $input->getArgument('date');
        $forceReloginAt = $this->parseDate($dateArg);

        if (null === $forceReloginAt) {
            $message = sprintf(
                'Invalid date format: "%s"',
                $dateArg ?? '',
            );
            $this->logger->error($message);
            $io->error($message);

            return Command::INVALID;
        }

        $isCompany = boolval($input->getOption('company-users'));
        $entityClass = $isCompany
            ? CompanyUser::class
            : User::class;
        $label = $isCompany
            ? 'company users'
            : 'users';

        $message = sprintf(
            'Updating forceReloginAt for %s at %s.',
            $label,
            $forceReloginAt->format(DateTime::ATOM),
        );
        $this->logger->info($message);

        $updated = $this->runUpdate(
            $entityClass,
            $forceReloginAt,
        );

        $message = sprintf(
            'Updated forceReloginAt for %d %s.',
            $updated,
            $label,
        );
        $this->logger->info($message);
        $io->success($message);

        return Command::SUCCESS;
    }

    private function parseDate(?string $dateStr): ?DateTime
    {
        if (null === $dateStr) {
            return AssociationYear::fromDate(new DateTime())->getStartDate();
        }

        try {
            return new DateTime($dateStr);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param class-string $entityClass
     */
    private function runUpdate(
        string $entityClass,
        DateTime $forceReloginAt,
    ): int {
        return (int) $this->entityManager->createQueryBuilder()
            ->update(
                $entityClass,
                'u',
            )
            ->set(
                'u.forceReloginAt',
                ':forceReloginAt',
            )
            ->setParameter(
                'forceReloginAt',
                $forceReloginAt,
                Types::DATETIME_MUTABLE,
            )
            ->getQuery()
            ->execute();
    }
}
