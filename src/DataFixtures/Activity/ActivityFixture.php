<?php

declare(strict_types=1);

namespace App\DataFixtures\Activity;

use App\DataFixtures\Decision\MemberFixture;
use App\Entity\Activity\Activity;
use App\Entity\Activity\ActivityLabel;
use App\Entity\Activity\ActivityLocalisedText;
use App\Entity\Activity\Enums\ActivityCategories;
use App\Entity\Activity\SignupList;
use App\Entity\Decision\Member;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

class ActivityFixture extends Fixture implements DependentFixtureInterface
{
    #[Override]
    public function load(ObjectManager $manager): void
    {
        // Ordered chronologically by begin time so the rows are inserted (and auto-incremented) in that order.
        $activities = [
            // Past (already happened), approved, organised by a discharged member.
            [
                'creator' => 8021,
                'status' => Activity::STATUS_APPROVED,
                'beginTime' => '-2 months 20:00',
                'endTime' => '-2 months 23:30',
                'category' => ActivityCategories::Recreational,
                'requireGEFLITST' => false,
                'requireZettle' => false,
                'name' => [
                    'en' => 'Movie Night',
                    'nl' => 'Filmavond',
                ],
                'location' => [
                    'en' => 'Common Room',
                    'nl' => 'Huiskamer',
                ],
                'costs' => [
                    'en' => 'Free',
                    'nl' => 'Gratis',
                ],
                'description' => [
                    'en' => 'An evening of films and popcorn.',
                    'nl' => 'Een avond vol films en popcorn.',
                ],
                'labels' => [
                    ActivityLabelFixture::REFERENCE_DUTCH_ONLY,
                    ActivityLabelFixture::REFERENCE_EXTERNALS,
                ],
                'signupLists' => [
                    [
                        'name' => [
                            'en' => 'Attendance',
                            'nl' => 'Aanwezigheid',
                        ],
                        'openDate' => '-3 months 12:00',
                        'closeDate' => '-2 months 18:00',
                        'onlyGEWIS' => true,
                        'displaySubscribedNumber' => true,
                        'limitedCapacity' => false,
                        'presenceTaken' => true,
                    ],
                ],
            ],
            // Past (already happened), approved, organised by a discharged member.
            // Closed signup list with presence taken.
            [
                'creator' => 8023,
                'status' => Activity::STATUS_APPROVED,
                'beginTime' => '-3 weeks 19:30',
                'endTime' => '-3 weeks 22:00',
                'category' => ActivityCategories::Workshop,
                'requireGEFLITST' => false,
                'requireZettle' => false,
                'name' => [
                    'en' => 'Workshop',
                    'nl' => 'Workshop',
                ],
                'location' => [
                    'en' => 'Room 2',
                    'nl' => 'Zaal 2',
                ],
                'costs' => [
                    'en' => '5 euro',
                    'nl' => '5 euro',
                ],
                'description' => [
                    'en' => 'A hands-on workshop.',
                    'nl' => 'Een praktische workshop.',
                ],
                'labels' => [
                    ActivityLabelFixture::REFERENCE_MASTER,
                    ActivityLabelFixture::REFERENCE_ENGLISH_ONLY,
                ],
                'signupLists' => [
                    [
                        'name' => [
                            'en' => 'Participants',
                            'nl' => 'Deelnemers',
                        ],
                        'openDate' => '-6 weeks 12:00',
                        'closeDate' => '-4 weeks 12:00',
                        'onlyGEWIS' => true,
                        'displaySubscribedNumber' => true,
                        'limitedCapacity' => true,
                        'presenceTaken' => true,
                    ],
                ],
            ],
            // Upcoming, approved, organised by a board member.
            [
                'creator' => 8025,
                'status' => Activity::STATUS_APPROVED,
                'beginTime' => '+2 weeks 19:00',
                'endTime' => '+2 weeks 23:00',
                'category' => ActivityCategories::SocialDrink,
                'requireGEFLITST' => false,
                'requireZettle' => false,
                'name' => [
                    'en' => 'Monthly Drink',
                    'nl' => 'Maandelijkse Borrel',
                ],
                'location' => [
                    'en' => 'Association Room',
                    'nl' => 'Verenigingskamer',
                ],
                'costs' => [
                    'en' => 'Free',
                    'nl' => 'Gratis',
                ],
                'description' => [
                    'en' => 'Join us for the monthly drink.',
                    'nl' => 'Kom langs op de maandelijkse borrel.',
                ],
            ],
            // Upcoming, awaiting approval, organised by an active (external) member.
            [
                'creator' => 8017,
                'status' => Activity::STATUS_TO_APPROVE,
                'beginTime' => '+3 weeks 12:30',
                'endTime' => '+3 weeks 14:00',
                'category' => ActivityCategories::Education,
                'requireGEFLITST' => false,
                'requireZettle' => false,
                'name' => [
                    'en' => 'Lunch Lecture',
                    'nl' => 'Lunchlezing',
                ],
                'location' => [
                    'en' => 'Lecture Hall 1',
                    'nl' => 'Collegezaal 1',
                ],
                'costs' => [
                    'en' => 'Free',
                    'nl' => 'Gratis',
                ],
                'description' => [
                    'en' => 'A lunch lecture by an industry guest.',
                    'nl' => 'Een lunchlezing door een gast uit het bedrijfsleven.',
                ],
                'labels' => [
                    ActivityLabelFixture::REFERENCE_FIRST_YEAR,
                    ActivityLabelFixture::REFERENCE_ENGLISH_ONLY,
                ],
            ],
            // Upcoming, approved, organised by an active member, needs photographer + payment terminal.
            // Has a single, currently open signup list with limited capacity.
            [
                'creator' => 8010,
                'status' => Activity::STATUS_APPROVED,
                'beginTime' => '+1 month 17:00',
                'endTime' => '+1 month 22:00',
                'category' => ActivityCategories::Party,
                'requireGEFLITST' => true,
                'requireZettle' => true,
                'name' => [
                    'en' => 'Gala',
                    'nl' => 'Gala',
                ],
                'location' => [
                    'en' => 'City Hall',
                    'nl' => 'Stadhuis',
                ],
                'costs' => [
                    'en' => '25 euro',
                    'nl' => '25 euro',
                ],
                'description' => [
                    'en' => 'The annual gala dinner.',
                    'nl' => 'Het jaarlijkse galadiner.',
                ],
                'signupLists' => [
                    [
                        'name' => [
                            'en' => 'Attendance',
                            'nl' => 'Aanwezigheid',
                        ],
                        'openDate' => '-1 week 12:00',
                        'closeDate' => '+3 weeks 12:00',
                        'onlyGEWIS' => true,
                        'displaySubscribedNumber' => true,
                        'limitedCapacity' => true,
                    ],
                ],
            ],
            // Upcoming, approved, organised by a board member. Two signup lists, one promoted.
            [
                'creator' => 8026,
                'status' => Activity::STATUS_APPROVED,
                'beginTime' => '+5 weeks 18:00',
                'endTime' => '+5 weeks 23:59',
                'category' => ActivityCategories::Conference,
                'requireGEFLITST' => true,
                'requireZettle' => true,
                'name' => [
                    'en' => 'Symposium',
                    'nl' => 'Symposium',
                ],
                'location' => [
                    'en' => 'Auditorium',
                    'nl' => 'Auditorium',
                ],
                'costs' => [
                    'en' => 'Free',
                    'nl' => 'Gratis',
                ],
                'description' => [
                    'en' => 'A day full of talks, followed by dinner.',
                    'nl' => 'Een dag vol lezingen, gevolgd door een diner.',
                ],
                'labels' => [
                    ActivityLabelFixture::REFERENCE_MASTER,
                    ActivityLabelFixture::REFERENCE_PHD,
                    ActivityLabelFixture::REFERENCE_ENGLISH_ONLY,
                ],
                'signupLists' => [
                    [
                        'name' => [
                            'en' => 'Talks',
                            'nl' => 'Lezingen',
                        ],
                        'openDate' => '-1 week 12:00',
                        'closeDate' => '+4 weeks 12:00',
                        'onlyGEWIS' => false,
                        'displaySubscribedNumber' => true,
                        'limitedCapacity' => false,
                        'promoted' => true,
                    ],
                    [
                        'name' => [
                            'en' => 'Dinner',
                            'nl' => 'Diner',
                        ],
                        'openDate' => '-1 week 12:00',
                        'closeDate' => '+3 weeks 12:00',
                        'onlyGEWIS' => true,
                        'displaySubscribedNumber' => false,
                        'limitedCapacity' => true,
                    ],
                ],
            ],
            // Upcoming career activity, approved, organised by an active (external) member.
            // Signup list open to non-GEWIS members.
            [
                'creator' => 8018,
                'status' => Activity::STATUS_APPROVED,
                'beginTime' => '+6 weeks 13:00',
                'endTime' => '+6 weeks 17:00',
                'category' => ActivityCategories::Career,
                'requireGEFLITST' => false,
                'requireZettle' => false,
                'name' => [
                    'en' => 'Career Day',
                    'nl' => 'Carrièredag',
                ],
                'location' => [
                    'en' => 'Atlas Building',
                    'nl' => 'Atlasgebouw',
                ],
                'costs' => [
                    'en' => 'Free',
                    'nl' => 'Gratis',
                ],
                'description' => [
                    'en' => 'Meet companies and explore your future career.',
                    'nl' => 'Ontmoet bedrijven en verken je toekomstige carrière.',
                ],
                'labels' => [
                    ActivityLabelFixture::REFERENCE_FIRST_YEAR,
                    ActivityLabelFixture::REFERENCE_BACHELOR,
                    ActivityLabelFixture::REFERENCE_MASTER,
                ],
                'signupLists' => [
                    [
                        'name' => [
                            'en' => 'Registration',
                            'nl' => 'Registratie',
                        ],
                        'openDate' => '-3 days 12:00',
                        'closeDate' => '+5 weeks 12:00',
                        'onlyGEWIS' => false,
                        'displaySubscribedNumber' => true,
                        'limitedCapacity' => false,
                    ],
                ],
            ],
        ];

        foreach ($activities as $data) {
            $activity = new Activity();
            $activity->setName(new ActivityLocalisedText($data['name']['en'], $data['name']['nl']));
            $activity->setLocation(new ActivityLocalisedText($data['location']['en'], $data['location']['nl']));
            $activity->setCosts(new ActivityLocalisedText($data['costs']['en'], $data['costs']['nl']));
            $activity->setDescription(
                new ActivityLocalisedText(
                    $data['description']['en'],
                    $data['description']['nl'],
                ),
            );
            $activity->setBeginTime(new DateTime($data['beginTime']));
            $activity->setEndTime(new DateTime($data['endTime']));
            $activity->setCreator($this->getReference('member-' . $data['creator'], Member::class));
            $activity->setStatus($data['status']);
            $activity->setCategory($data['category']);
            $activity->setRequireGEFLITST($data['requireGEFLITST']);
            $activity->setRequireZettle($data['requireZettle']);

            foreach ($data['labels'] ?? [] as $labelReference) {
                $activity->addLabel($this->getReference($labelReference, ActivityLabel::class));
            }

            $manager->persist($activity);

            foreach ($data['signupLists'] ?? [] as $signupListData) {
                $signupList = $this->createSignupList($signupListData);
                $activity->addSignupList($signupList);
                $manager->persist($signupList);
            }
        }

        $manager->flush();
    }

    /**
     * @param array<string, mixed> $data
     * @psalm-param array{
     *     name: array{en: string, nl: string},
     *     openDate: string,
     *     closeDate: string,
     *     onlyGEWIS: bool,
     *     displaySubscribedNumber: bool,
     *     limitedCapacity: bool,
     *     promoted?: bool,
     *     presenceTaken?: bool,
     * } $data
     */
    private function createSignupList(array $data): SignupList
    {
        $signupList = new SignupList();
        $signupList->setName(new ActivityLocalisedText($data['name']['en'], $data['name']['nl']));
        $signupList->setOpenDate(new DateTime($data['openDate']));
        $signupList->setCloseDate(new DateTime($data['closeDate']));
        $signupList->setOnlyGEWIS($data['onlyGEWIS']);
        $signupList->setDisplaySubscribedNumber($data['displaySubscribedNumber']);
        $signupList->setLimitedCapacity($data['limitedCapacity']);
        $signupList->setPromoted($data['promoted'] ?? false);
        $signupList->setPresenceTaken($data['presenceTaken'] ?? false);

        return $signupList;
    }

    /**
     * @return array<array-key, class-string<Fixture>>
     */
    #[Override]
    public function getDependencies(): array
    {
        return [
            MemberFixture::class,
            ActivityLabelFixture::class,
        ];
    }
}
