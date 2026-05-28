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
            // Past, two association years ago (AY 2023-2024) — appears under that heading in the archive and
            // exercises the same-day, past-year date format.
            [
                'creator' => 8020,
                'status' => Activity::STATUS_APPROVED,
                'beginTime' => '2024-02-20 19:00',
                'endTime' => '2024-02-20 22:00',
                'category' => ActivityCategories::Cultural,
                'requireGEFLITST' => false,
                'requireZettle' => false,
                'name' => [
                    'en' => 'Museum Visit',
                    'nl' => 'Museumbezoek',
                ],
                'location' => [
                    'en' => 'Van Abbemuseum',
                    'nl' => 'Van Abbemuseum',
                ],
                'costs' => [
                    'en' => '5 euro',
                    'nl' => '5 euro',
                ],
                'description' => [
                    'en' => 'A guided evening tour of the modern art collection.',
                    'nl' => 'Een rondleiding langs de moderne kunstcollectie.',
                ],
            ],
            // Past, multi-day, in a previous calendar year (fixed dates) — exercises the multi-day + year date format.
            [
                'creator' => 8024,
                'status' => Activity::STATUS_APPROVED,
                'beginTime' => '2025-12-12 17:00',
                'endTime' => '2025-12-14 12:00',
                'category' => ActivityCategories::Weekend,
                'requireGEFLITST' => false,
                'requireZettle' => false,
                'name' => [
                    'en' => 'Winter Weekend',
                    'nl' => 'Winterweekend',
                ],
                'location' => [
                    'en' => 'Ardennes',
                    'nl' => 'Ardennen',
                ],
                'costs' => [
                    'en' => '75 euro',
                    'nl' => '75 euro',
                ],
                'description' => [
                    'en' => 'A three-day winter getaway in the Ardennes with hikes, board games, and plenty of '
                        . '**hot chocolate**. Cabins are shared; transport is arranged together by carpool.',
                    'nl' => 'Een driedaags winterweekend in de Ardennen met wandelingen, bordspellen en volop '
                        . '**warme chocolademelk**. De huisjes worden gedeeld; vervoer regelen we samen via carpool.',
                ],
                'labels' => [
                    ActivityLabelFixture::REFERENCE_DUTCH_ONLY,
                ],
                'signupLists' => [
                    [
                        'name' => [
                            'en' => 'Attendance',
                            'nl' => 'Aanwezigheid',
                        ],
                        'openDate' => '2025-11-01 12:00',
                        'closeDate' => '2025-12-01 12:00',
                        'onlyGEWIS' => true,
                        'displaySubscribedNumber' => true,
                        'limitedCapacity' => true,
                        'presenceTaken' => true,
                    ],
                ],
            ],
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
                    'en' => "Grab a seat and a blanket for a cosy **movie night** at the association.\n\n"
                        . 'We screen two films back to back, with a short break for free popcorn and drinks in '
                        . "between. The theme changes every month and is decided by a poll among members.\n\n"
                        . 'No sign-up needed for the second film — just show up. Expect the evening to run until '
                        . 'well past midnight, so bring something comfortable to sit on.',
                    'nl' => "Pak een stoel en een dekentje voor een gezellige **filmavond** bij de vereniging.\n\n"
                        . 'We vertonen twee films achter elkaar, met een korte pauze voor gratis popcorn en drankjes '
                        . "ertussen. Het thema wisselt elke maand en wordt bepaald via een poll onder leden.\n\n"
                        . 'Voor de tweede film is geen aanmelding nodig — kom gewoon langs. De avond loopt door tot '
                        . 'ruim na middernacht, dus neem iets comfortabels mee om op te zitten.',
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
            // Ongoing right now (began earlier, ends later) — exercises the "ongoing for %duration%" note. A drop-in
            // borrel has no sign-up: a sign-up list can never still be open once the activity has started.
            [
                'creator' => 8012,
                'status' => Activity::STATUS_APPROVED,
                'beginTime' => '-1 hour',
                'endTime' => '+3 hours',
                'category' => ActivityCategories::SocialDrink,
                'requireGEFLITST' => false,
                'requireZettle' => false,
                'name' => [
                    'en' => 'Open Borrel',
                    'nl' => 'Open Borrel',
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
                    'en' => 'Drop by the association room for a drink — we are open right now.',
                    'nl' => 'Kom langs in de verenigingskamer voor een drankje — we zijn nu open.',
                ],
            ],
            // Upcoming, with a sign-up that closes within a day — exercises the imminent-deadline colour (text-danger)
            // on an activity that has not started yet, so its sign-up can legitimately still be open.
            [
                'creator' => 8013,
                'status' => Activity::STATUS_APPROVED,
                'beginTime' => '+2 days 20:00',
                'endTime' => '+2 days 23:00',
                'category' => ActivityCategories::Recreational,
                'requireGEFLITST' => false,
                'requireZettle' => false,
                'name' => [
                    'en' => 'Pub Quiz',
                    'nl' => 'Pubquiz',
                ],
                'location' => [
                    'en' => 'Common Room',
                    'nl' => 'Huiskamer',
                ],
                'costs' => [
                    'en' => '2 euro',
                    'nl' => '2 euro',
                ],
                'description' => [
                    'en' => 'Test your trivia knowledge in teams. Sign up soon — registration closes within a day!',
                    'nl' => 'Test je kennis in teams. Schrijf je snel in — de inschrijving sluit binnen een dag!',
                ],
                'signupLists' => [
                    [
                        'name' => [
                            'en' => 'Teams',
                            'nl' => 'Teams',
                        ],
                        'openDate' => '-2 days 12:00',
                        'closeDate' => '+12 hours',
                        'onlyGEWIS' => true,
                        'displaySubscribedNumber' => true,
                        'limitedCapacity' => true,
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
                'signupLists' => [
                    [
                        'name' => [
                            'en' => 'Attendance',
                            'nl' => 'Aanwezigheid',
                        ],
                        'openDate' => '-2 days 12:00',
                        'closeDate' => '+4 days 18:00',
                        'onlyGEWIS' => true,
                        'displaySubscribedNumber' => true,
                        'limitedCapacity' => false,
                    ],
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
                    'en' => "## Programme\n\n"
                        . 'The annual **GEWIS Symposium** brings together students, alumni, and companies for a full '
                        . "day of talks on the latest in computer science and mathematics.\n\n"
                        . "The day is split into several tracks:\n\n"
                        . "- Morning keynotes by leading researchers\n"
                        . "- Hands-on afternoon workshops\n"
                        . "- An evening dinner with drinks to close the day\n\n"
                        . 'Whether you are a first-year or a seasoned PhD candidate, there is something for everyone. '
                        . 'Doors open at 09:00; check the [programme booklet](https://gewis.nl) for the full schedule '
                        . 'and come prepared to learn something new.',
                    'nl' => "## Programma\n\n"
                        . 'Het jaarlijkse **GEWIS Symposium** brengt studenten, alumni en bedrijven samen voor een dag '
                        . "vol lezingen over de nieuwste ontwikkelingen in informatica en wiskunde.\n\n"
                        . "De dag bestaat uit verschillende tracks:\n\n"
                        . "- Ochtendkeynotes door vooraanstaande onderzoekers\n"
                        . "- Praktische workshops in de middag\n"
                        . "- Een diner met borrel als afsluiting\n\n"
                        . 'Of je nu eerstejaars of een ervaren promovendus bent, er is voor ieder wat wils. '
                        . 'De deuren openen om 09:00; bekijk het [programmaboekje](https://gewis.nl) voor het '
                        . 'volledige schema en kom klaar om iets nieuws te leren.',
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
                    'en' => "Curious about life **after** your studies? The Career Day is the place to be.\n\n"
                        . 'Dozens of companies — from fast-growing start-ups to established multinationals — will be '
                        . "present to tell you about internships, graduation projects, and full-time positions.\n\n"
                        . "What to expect:\n\n"
                        . "- One-on-one chats at company stands\n"
                        . "- Short company pitches throughout the day\n"
                        . "- Free lunch and an informal closing drink\n\n"
                        . 'Bring a few copies of your CV and dress to impress. Pre-registration is appreciated but '
                        . 'walk-ins are always welcome.',
                    'nl' => "Benieuwd naar het leven **na** je studie? Dan is de Carrièredag dé plek om te zijn.\n\n"
                        . 'Tientallen bedrijven — van snelgroeiende start-ups tot gevestigde multinationals — zijn '
                        . "aanwezig om te vertellen over stages, afstudeerprojecten en vaste banen.\n\n"
                        . "Wat je kunt verwachten:\n\n"
                        . "- Persoonlijke gesprekken bij bedrijfsstands\n"
                        . "- Korte bedrijfspitches gedurende de dag\n"
                        . "- Gratis lunch en een informele afsluitende borrel\n\n"
                        . 'Neem een paar exemplaren van je cv mee en kleed je netjes. Aanmelden vooraf wordt '
                        . 'gewaardeerd, maar je bent ook zonder aanmelding van harte welkom.',
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
            // Upcoming, multi-day (spans several days) — exercises the multi-day date format for future activities.
            [
                'creator' => 8027,
                'status' => Activity::STATUS_APPROVED,
                'beginTime' => '+7 weeks 09:00',
                'endTime' => '+7 weeks +2 days 17:00',
                'category' => ActivityCategories::Conference,
                'requireGEFLITST' => true,
                'requireZettle' => true,
                'name' => [
                    'en' => 'Study Conference',
                    'nl' => 'Studiecongres',
                ],
                'location' => [
                    'en' => 'Conference Centre',
                    'nl' => 'Congrescentrum',
                ],
                'costs' => [
                    'en' => '40 euro',
                    'nl' => '40 euro',
                ],
                'description' => [
                    'en' => 'A three-day conference packed with lectures, workshops, and excursions. Accommodation '
                        . 'and most meals are included; have a look at the schedule before you sign up.',
                    'nl' => 'Een driedaags congres vol lezingen, workshops en excursies. Overnachting en de meeste '
                        . 'maaltijden zijn inbegrepen; bekijk het programma voordat je je inschrijft.',
                ],
                'labels' => [
                    ActivityLabelFixture::REFERENCE_MASTER,
                    ActivityLabelFixture::REFERENCE_PHD,
                ],
                'signupLists' => [
                    [
                        'name' => [
                            'en' => 'Attendance',
                            'nl' => 'Aanwezigheid',
                        ],
                        'openDate' => '-1 week 12:00',
                        'closeDate' => '+5 weeks 12:00',
                        'onlyGEWIS' => true,
                        'displaySubscribedNumber' => true,
                        'limitedCapacity' => true,
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
