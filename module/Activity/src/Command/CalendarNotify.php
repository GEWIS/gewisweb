<?php

declare(strict_types=1);

namespace Activity\Command;

use Activity\Service\ActivityCalendar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CalendarNotify extends Command
{
    public function __construct(private readonly ActivityCalendar $calendarService)
    {
        parent::__construct();
    }

    public function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $this->calendarService->sendOverdueNotifications();

        return 1;
    }
}
