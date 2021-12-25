<?php

namespace Photo\Command;

use Photo\Service\Photo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WeeklyPhoto extends Command
{
    /**
     * @var Photo
     */
    private Photo $photoService;

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
        $weeklyPhoto = $this->photoService->generatePhotoOfTheWeek();

        if (is_null($weeklyPhoto)) {
            echo "No photo of the week chosen, were any photos viewed?\n";
            return 0;
        } else {
            echo 'Photo of the week set to photo: ' . $weeklyPhoto->getPhoto()->getId();
            return 1;
        }
    }

    /**
     * @param Photo $photoService
     */
    public function setPhotoService(Photo $photoService): void
    {
        $this->photoService = $photoService;
    }
}
