<?php

declare(strict_types=1);

namespace User\Command;

use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use User\Authentication\Service\LoginAttempt as LoginAttemptService;

#[AsCommand(
    name: 'user:gdpr:delete-old-loginattempts',
    description: 'Delete (failed) login attempts older than 3 months',
)]
class DeleteOldLoginAttempts extends Command
{
    public function __construct(private readonly LoginAttemptService $loginAttemptService)
    {
        parent::__construct();
    }

    #[Override]
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $this->loginAttemptService->deletedOldLoginAttempts();

        return Command::SUCCESS;
    }
}
