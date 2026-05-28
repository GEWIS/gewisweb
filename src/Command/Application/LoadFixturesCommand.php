<?php

declare(strict_types=1);

namespace App\Command\Application;

use Doctrine\Bundle\FixturesBundle\Loader\FixturesProvider;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use function sprintf;

#[AsCommand(
    name: 'app:fixtures:load',
    description: 'Seed the database with data fixtures.',
)]
final class LoadFixturesCommand extends Command
{
    public function __construct(
        #[Autowire(service: 'doctrine.fixtures.loader')]
        private readonly FixturesProvider $fixturesProvider,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $ui = new SymfonyStyle(
            $input,
            $output,
        );
        $connection = $this->entityManager->getConnection();

        if (
            !$ui->confirm(
                sprintf(
                    'Careful, database "%s" will be purged. Do you want to continue?',
                    $connection->getDatabase() ?? '',
                ),
                !$input->isInteractive(),
            )
        ) {
            return Command::SUCCESS;
        }

        $fixtures = $this->fixturesProvider->getFixtures();
        if ([] === $fixtures) {
            $ui->error('Could not find any fixture services to load.');

            return Command::FAILURE;
        }

        $purger = new ORMPurger($this->entityManager);
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);
        $executor = new ORMExecutor($this->entityManager);

        $ui->text('Loading fixtures into the database...');

        // Temporarily disable foreign key checks around both the purge and the load. Large parts of the database lack
        // explicit CASCADEs (to avoid data loss when syncing with ReportDB/GEWISDB), so the default dependency-ordered
        // purge cannot resolve self-referential and cross-table constraints (e.g. SubDecision) over existing data.
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

        try {
            // Purge separately (TRUNCATE issues an implicit commit, which would break the executor's transaction) and
            // then load with append so the executor only inserts, while still wiring up fixture references.
            $purger->purge();
            $executor->execute(
                $fixtures,
                true,
            );
        } finally {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        }

        $ui->success('Loaded fixtures!');

        return Command::SUCCESS;
    }
}
