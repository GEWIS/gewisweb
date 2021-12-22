<?php

namespace Activity\Command;

use Activity\Service\ActivityCalendar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CalendarNotify extends Command
{
    /**
     * @var ActivityCalendar
     */
    private ActivityCalendar $calendarService;

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $this->calendarService->sendOverdueNotifications();
        return 1;
    }

    /**
     * @param ActivityCalendar $calendarService
     *
     * @return void
     */
    public function setCalendarService(ActivityCalendar $calendarService): void
    {
        $this->calendarService = $calendarService;
    }
}
