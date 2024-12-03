<?php

declare(strict_types=1);

namespace Application\Command;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'application:fixtures:load',
    description: 'Seed the database with data fixtures.',
)]
class LoadFixtures extends Command
{
    private const array FIXTURES = [
        // './module/Activity/test/Seeder',
        // './module/Company/test/Seeder',
        './module/Decision/test/Seeder',
        // './module/Education/test/Seeder',
        // './module/Frontpage/test/Seeder',
        // './module/Photo/test/Seeder',
        './module/User/test/Seeder',
    ];

    public function __construct(private readonly EntityManager $entityManager)
    {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $loader = new Loader();
        $purger = new ORMPurger();
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);
        $executor = new ORMExecutor($this->entityManager, $purger);

        foreach ($this::FIXTURES as $fixture) {
            $loader->loadFromDirectory($fixture);
        }

        $output->writeln('<info>Loading fixtures into the database...</info>');

        $connection = $this->entityManager->getConnection();
        try {
            // Temporarily disable FK constraint checks. This is necessary because large parts of our database do not
            // have explicit CASCADEs set to prevent data loss when syncing with ReportDB (GEWISDB).
            // The try-catch is necessary to hide some error messages (because the executeStatement).
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
            $executor->execute($loader->getFixtures());
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        } catch (Throwable) {
        }

        $output->writeln('<info>Loaded fixtures!</info>');

        return Command::SUCCESS;
    }
}
