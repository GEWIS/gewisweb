<?php

declare(strict_types=1);

namespace App\DataFixtures\Decision;

use App\Entity\Decision\AssociationYear;
use App\Entity\Decision\Enums\MeetingTypes;
use App\Entity\Decision\Meeting;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Override;

use function range;

class MeetingFixture extends Fixture
{
    #[Override]
    public function load(ObjectManager $manager): void
    {
        $today = new DateTime();

        foreach (MeetingTypes::cases() as $meetingType) {
            foreach (
                range(
                    0,
                    3,
                ) as $meetingNumber
            ) {
                $meeting = new Meeting();
                $meeting->setType($meetingType);
                $meeting->setNumber($meetingNumber);

                // 2 meetings in the past, 1 today, and 1 in the future.
                if (3 > $meetingNumber) {
                    $meetingDate = (clone $today)->modify('-' . (2 - $meetingNumber) . ' days');
                } else {
                    $meetingDate = AssociationYear::fromDate($today)->getEndDate();
                }

                $meeting->setDate($meetingDate);

                $manager->persist($meeting);
                $this->addReference(
                    'meeting-' . $meetingType->value . '-' . $meetingNumber,
                    $meeting,
                );
            }

            $manager->flush();
        }
    }
}
