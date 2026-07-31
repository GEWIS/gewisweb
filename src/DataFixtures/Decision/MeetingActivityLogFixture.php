<?php

declare(strict_types=1);

namespace App\DataFixtures\Decision;

use App\DataFixtures\User\UserFixture;
use App\Entity\Decision\Enums\MeetingActivityVerbs;
use App\Entity\Decision\Meeting;
use App\Entity\Decision\MeetingActivityLog;
use App\Entity\User\User;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

/**
 * A small activity feed for ALV-1 plus one library entry that belongs to no meeting.
 */
class MeetingActivityLogFixture extends Fixture implements DependentFixtureInterface
{
    #[Override]
    public function load(ObjectManager $manager): void
    {
        $actor = $this->getReference(
            'user-8000',
            User::class,
        );
        $meeting = $this->getReference(
            'meeting-ALV-1',
            Meeting::class,
        );

        $entries = [
            [
                MeetingActivityVerbs::PointCreated,
                '7a Budget',
                '-3 days',
            ],
            [
                MeetingActivityVerbs::DocumentUploaded,
                'Budget (v2.1)',
                '-2 days',
            ],
            [
                MeetingActivityVerbs::PointUpdated,
                '7b Budget explanation',
                '-1 day',
            ],
        ];

        foreach ($entries as [$verb, $subject, $moment]) {
            $entry = new MeetingActivityLog();
            $entry->setActor($actor);
            $entry->setMeeting($meeting);
            $entry->setVerb($verb);
            $entry->setSubject($subject);
            $entry->setCreatedAt(new DateTime($moment));

            $manager->persist($entry);
        }

        $libraryEntry = new MeetingActivityLog();
        $libraryEntry->setActor($actor);
        $libraryEntry->setVerb(MeetingActivityVerbs::ReferenceDocumentCreated);
        $libraryEntry->setSubject('Scenarios and Procedures');
        $libraryEntry->setCreatedAt(new DateTime('-4 weeks'));

        $manager->persist($libraryEntry);
        $manager->flush();
    }

    /**
     * @return class-string<Fixture>[]
     */
    #[Override]
    public function getDependencies(): array
    {
        return [
            MeetingFixture::class,
            UserFixture::class,
        ];
    }
}
