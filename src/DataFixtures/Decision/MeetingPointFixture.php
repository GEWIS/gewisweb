<?php

declare(strict_types=1);

namespace App\DataFixtures\Decision;

use App\Entity\Decision\Meeting;
use App\Entity\Decision\MeetingPoint;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

use function sprintf;

/**
 * Agenda points on the GMMs and CMs; BMs never have them. The complete GMM carries an exact-numbered point plus a
 * lettered "7a"/"7b" pair and misses a point for one of its decisions, so the decision matching (exact wins, first
 * lettered variant wins, unmatched decisions) can be exercised against the decisions seeded by {@see DecisionFixture}.
 */
class MeetingPointFixture extends Fixture implements DependentFixtureInterface
{
    private const array POINTS = [
        'meeting-gmm-complete' => [
            [
                '2',
                'Agenda',
            ],
            [
                '3',
                'Minutes previous GMM',
            ],
            [
                '7a',
                'Budget',
            ],
            [
                '7b',
                'Budget explanation',
            ],
        ],
        'meeting-gmm-upcoming' => [
            [
                '1',
                'Opening',
            ],
            [
                '2',
                'Agenda',
            ],
            [
                '3',
                'Committee updates',
            ],
        ],
        'meeting-cm-past' => [
            [
                '1',
                'Opening',
            ],
            [
                '2',
                'Quarterly report',
            ],
        ],
    ];

    #[Override]
    public function load(ObjectManager $manager): void
    {
        foreach (self::POINTS as $meetingReference => $points) {
            $meeting = $this->getReference(
                $meetingReference,
                Meeting::class,
            );

            foreach ($points as $position => [$number, $title]) {
                $point = new MeetingPoint();
                $point->setMeeting($meeting);
                $point->setNumber($number);
                $point->setTitle($title);
                $point->setDisplayPosition($position);

                $manager->persist($point);
                $this->addReference(
                    sprintf(
                        '%s-point-%s',
                        $meetingReference,
                        $number,
                    ),
                    $point,
                );
            }
        }

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
