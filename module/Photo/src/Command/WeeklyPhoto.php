<?php

declare(strict_types=1);

namespace Photo\Command;

use Photo\Service\Photo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WeeklyPhoto extends Command
{
    public function __construct(private readonly Photo $photoService)
    {
        parent::__construct();
    }

    public function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $weeklyPhoto = $this->photoService->generatePhotoOfTheWeek();

        if (null === $weeklyPhoto) {
            echo "No photo of the week chosen, were any photos viewed?\n";

            return 0;
        }

        echo 'Photo of the week set to photo: ' . $weeklyPhoto->getPhoto()->getId();

        return 1;
    }
}
