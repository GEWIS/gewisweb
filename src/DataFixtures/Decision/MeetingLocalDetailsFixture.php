<?php

declare(strict_types=1);

namespace App\DataFixtures\Decision;

use App\Entity\Decision\Meeting;
use App\Entity\Decision\MeetingLocalDetails;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

/**
 * Time and place for the upcoming ALV.
 */
class MeetingLocalDetailsFixture extends Fixture implements DependentFixtureInterface
{
    #[Override]
    public function load(ObjectManager $manager): void
    {
        $details = new MeetingLocalDetails();
        $details->setMeeting($this->getReference(
            'meeting-ALV-3',
            Meeting::class,
        ));
        $details->setStartTime(new DateTime('20:00'));
        $details->setLocation('Auditorium 4');

        $manager->persist($details);
        $manager->flush();
    }

    /**
     * @return class-string<Fixture>[]
     */
    #[Override]
    public function getDependencies(): array
    {
        return [MeetingFixture::class];
    }
}
