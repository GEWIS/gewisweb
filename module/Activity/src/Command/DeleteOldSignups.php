<?php

declare(strict_types=1);

namespace Activity\Command;

use Activity\Service\Signup as SignupService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'activity:gdpr:delete-old-signups',
    description: 'Delete sign-ups for activities older than 5 years',
)]
class DeleteOldSignups extends Command
{
    public function __construct(private readonly SignupService $signupService)
    {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $this->signupService->deleteOldSignups();

        return Command::SUCCESS;
    }
}
