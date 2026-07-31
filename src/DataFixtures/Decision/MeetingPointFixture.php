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
 * Agenda points on the past meetings. BV-0 carries an exact-numbered point plus a lettered "2a"/"2b" pair and BV-1 a
 * point that matches none of its decisions, so the decision matching (exact wins, first lettered variant wins,
 * unmatched decisions) can be exercised against the decisions seeded by {@see DecisionFixture}.
 */
class MeetingPointFixture extends Fixture implements DependentFixtureInterface
{
    private const array POINTS = [
        'ALV-0' => [
            [
                '2',
                'Agenda',
            ],
            [
                '3',
                'Decision list',
            ],
            [
                '4',
                'Minutes GMM',
            ],
        ],
        'ALV-1' => [
            [
                '2',
                'Agenda',
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
        'BV-0' => [
            [
                '1',
                'Opening',
            ],
            [
                '2a',
                'Committee foundations',
            ],
            [
                '2b',
                'Committee foundations (continued)',
            ],
        ],
        'BV-1' => [
            [
                '3',
                'Any other business',
            ],
        ],
    ];

    #[Override]
    public function load(ObjectManager $manager): void
    {
        foreach (self::POINTS as $meetingReference => $points) {
            $meeting = $this->getReference(
                'meeting-' . $meetingReference,
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
                        'meeting-point-%s-%s',
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
