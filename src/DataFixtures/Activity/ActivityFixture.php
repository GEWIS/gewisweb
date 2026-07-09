<?php

declare(strict_types=1);

namespace App\DataFixtures\Activity;

use App\DataFixtures\Decision\DecisionFixture;
use App\DataFixtures\Decision\MemberFixture;
use App\DataFixtures\User\UserFixture;
use App\Entity\Activity\Activity;
use App\Entity\Activity\ActivityLabel;
use App\Entity\Activity\ActivityLocalisedText;
use App\Entity\Activity\ActivityRevision;
use App\Entity\Activity\ActivityRevisionComment;
use App\Entity\Activity\Enums\ActivityCategories;
use App\Entity\Activity\Enums\AllocationMethod;
use App\Entity\Activity\Enums\DrawCutoffRule;
use App\Entity\Activity\Enums\SignupFieldTypes;
use App\Entity\Activity\ExternalSignup;
use App\Entity\Activity\Signup;
use App\Entity\Activity\SignupField;
use App\Entity\Activity\SignupFieldValue;
use App\Entity\Activity\SignupList;
use App\Entity\Activity\SignupOption;
use App\Entity\Activity\UserSignup;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Decision\Member;
use App\Entity\Decision\Organ;
use App\Entity\User\User;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

use function is_array;

class ActivityFixture extends Fixture implements DependentFixtureInterface
{
    #[Override]
    public function load(ObjectManager $manager): void
    {
        // Ordered chronologically by begin time so the rows are inserted (and auto-incremented) in that order.
        $activities = [
            // Past, two association years ago (AY 2023-2024): appears under that heading in the archive and
            // exercises the same-day, past-year date format.
            [
                'creator' => 8020,
                'status' => RevisionStatus::Approved,
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
            // Past, multi-day, in a previous calendar year (fixed dates): exercises the multi-day + year date format.
            [
                'creator' => 8024,
                'status' => RevisionStatus::Approved,
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
                'status' => RevisionStatus::Approved,
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
                'status' => RevisionStatus::Approved,
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
                        'onlyGEWIS' => false,
                        'displaySubscribedNumber' => true,
                        'limitedCapacity' => true,
                        'capacity' => 3,
                        'allocationMethod' => AllocationMethod::ConditionalDraw,
                        'drawCutoffRule' => DrawCutoffRule::OnClose,
                        'drawnAt' => '-4 weeks 12:00',
                        'drawnBy' => 8025,
                        'presenceTaken' => true,
                        // A closed, past, limited-capacity list exercising the full flow: 3 admitted (capacity 3) of
                        // whom 2 attended and 1 was a no-show, plus 2 on the waiting list (one a non-member external),
                        // an obvious backfill opportunity. Also covers extra fields and mixed membership types.
                        'fields' => [
                            [
                                'type' => SignupFieldTypes::Text,
                                'name' => [
                                    'en' => 'Dietary requirements',
                                    'nl' => 'Dieetwensen',
                                ],
                            ],
                            [
                                'type' => SignupFieldTypes::Choice,
                                'name' => [
                                    'en' => 'T-shirt size',
                                    'nl' => 'T-shirtmaat',
                                ],
                                'options' => [
                                    [
                                        'en' => 'S',
                                        'nl' => 'S',
                                    ],
                                    [
                                        'en' => 'M',
                                        'nl' => 'M',
                                    ],
                                    [
                                        'en' => 'L',
                                        'nl' => 'L',
                                    ],
                                ],
                            ],
                        ],
                        'subscribers' => [
                            [
                                'member' => 8005, // ordinary: admitted, attended
                                'drawn' => true,
                                'present' => true,
                                'answers' => [
                                    'Dietary requirements' => 'Vegetarian',
                                    'T-shirt size' => 'M',
                                ],
                            ],
                            [
                                'member' => 8006, // ordinary: admitted, attended
                                'drawn' => true,
                                'present' => true,
                                'answers' => ['T-shirt size' => 'L'],
                            ],
                            [
                                'member' => 8015, // external member: admitted, no-show
                                'drawn' => true,
                                'present' => false,
                                'answers' => [
                                    'Dietary requirements' => 'None',
                                    'T-shirt size' => 'S',
                                ],
                            ],
                            [
                                'member' => 8155, // graduate: waiting list
                                'drawn' => false,
                                'present' => false,
                                'answers' => ['T-shirt size' => 'M'],
                            ],
                        ],
                        'externals' => [
                            [
                                'fullName' => 'Alex Visitor', // non-member: waiting list
                                'email' => 'alex.visitor@example.org',
                                'drawn' => false,
                                'present' => false,
                                'answers' => [
                                    'Dietary requirements' => 'Gluten-free',
                                    'T-shirt size' => 'L',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            // Ongoing right now (began earlier, ends later): exercises the "ongoing for %duration%" note. A drop-in
            // borrel has no sign-up: a sign-up list can never still be open once the activity has started.
            [
                'creator' => 8012,
                'status' => RevisionStatus::Approved,
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
            // Upcoming, with a sign-up that closes within a day: exercises the imminent-deadline colour (text-danger)
            // on an activity that has not started yet, so its sign-up can legitimately still be open.
            [
                'creator' => 8013,
                'status' => RevisionStatus::Approved,
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
                'status' => RevisionStatus::Approved,
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
                'status' => RevisionStatus::Submitted,
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
                'status' => RevisionStatus::Approved,
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
                        // Upcoming limited list whose draw has not happened yet (sign-up still open, so the draw is
                        // blocked): all 4 sign-ups default to the waiting list, capacity 2 → "Admitted: 0 / 2".
                        'capacity' => 2,
                        'allocationMethod' => AllocationMethod::ConditionalDraw,
                        'drawCutoffRule' => DrawCutoffRule::OnClose,
                        'subscribers' => [
                            8005,
                            8006,
                            8007,
                            8008,
                        ],
                    ],
                ],
            ],
            // Upcoming, approved, organised by a board member. Two signup lists, one promoted.
            [
                'creator' => 8026,
                'status' => RevisionStatus::Approved,
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
                        'capacity' => 40,
                        // An external party (the venue) allocates the seats; admission is recorded by hand.
                        'allocationMethod' => AllocationMethod::ExternalParty,
                        'externalPolicyUrl' => 'https://example.org/venue-policy',
                    ],
                ],
            ],
            // Upcoming career activity, approved, organised by an active (external) member.
            // Signup list open to non-GEWIS members.
            [
                'creator' => 8018,
                'status' => RevisionStatus::Approved,
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
            // Upcoming, multi-day (spans several days): exercises the multi-day date format for future activities.
            [
                'creator' => 8027,
                'status' => RevisionStatus::Approved,
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
                        'capacity' => 30,
                        // A bespoke selection (study-phase mix); admission is recorded by hand.
                        'allocationMethod' => AllocationMethod::Custom,
                        'customMethodDescription' => 'Selected to balance bachelor, master and PhD attendees.',
                    ],
                ],
            ],
            // Upcoming, approved, board-organised, with a CLOSED limited sign-up list, so the draw is testable
            // end-to-end (sign-up over, activity still in the future, more sign-ups than places, not yet drawn).
            // NOTE: this list is due for the automated draw the moment it is seeded, so in dev a running scheduler
            // draws it within a minute; re-seed to demo the pre-draw state or the manual board fallback.
            [
                'creator' => 8025,
                'status' => RevisionStatus::Approved,
                'beginTime' => '+10 days 19:00',
                'endTime' => '+10 days 23:00',
                'category' => ActivityCategories::Recreational,
                'requireGEFLITST' => false,
                'requireZettle' => false,
                'name' => [
                    'en' => 'Excursion',
                    'nl' => 'Excursie',
                ],
                'location' => [
                    'en' => 'Brewery',
                    'nl' => 'Brouwerij',
                ],
                'costs' => [
                    'en' => '15 euro',
                    'nl' => '15 euro',
                ],
                'description' => [
                    'en' => 'A guided brewery tour with limited places.',
                    'nl' => 'Een rondleiding door een brouwerij met beperkt aantal plaatsen.',
                ],
                'signupLists' => [
                    [
                        'name' => [
                            'en' => 'Attendance',
                            'nl' => 'Aanwezigheid',
                        ],
                        'openDate' => '-2 weeks 12:00',
                        'closeDate' => '-1 day 12:00',
                        'onlyGEWIS' => true,
                        'displaySubscribedNumber' => true,
                        'limitedCapacity' => true,
                        'capacity' => 2,
                        'allocationMethod' => AllocationMethod::ConditionalDraw,
                        'drawCutoffRule' => DrawCutoffRule::OnClose,
                        'subscribers' => [
                            8005,
                            8006,
                            8007,
                            8008,
                        ],
                    ],
                ],
            ],
            // Upcoming, approved, but CANCELLED by the board: it stays publicly visible with a [CANCELLED] marker and a
            // notice, and all sign-up interaction is frozen (existing sign-ups are kept, but nobody can join/leave).
            [
                'creator' => 8025,
                'status' => RevisionStatus::Approved,
                'cancelled' => true,
                'beginTime' => '+3 weeks 20:00',
                'endTime' => '+3 weeks 23:59',
                'category' => ActivityCategories::SocialDrink,
                'requireGEFLITST' => false,
                'requireZettle' => false,
                'name' => [
                    'en' => 'Cancelled Gala',
                    'nl' => 'Geannuleerd Gala',
                ],
                'location' => [
                    'en' => 'Grand Hall',
                    'nl' => 'Grote Zaal',
                ],
                'costs' => [
                    'en' => '10 euro',
                    'nl' => '10 euro',
                ],
                'description' => [
                    'en' => 'This gala has unfortunately been cancelled.',
                    'nl' => 'Dit gala is helaas geannuleerd.',
                ],
                'signupLists' => [
                    [
                        'name' => [
                            'en' => 'Attendance',
                            'nl' => 'Aanwezigheid',
                        ],
                        'openDate' => '-2 days 12:00',
                        'closeDate' => '+1 week 18:00',
                        'onlyGEWIS' => true,
                        'displaySubscribedNumber' => true,
                        'limitedCapacity' => false,
                        'subscribers' => [
                            8005,
                            8006,
                        ],
                    ],
                ],
            ],
            // Upcoming, approved, but UNPUBLISHED by the board: it is removed from public view entirely (listings,
            // calendar, and a 404 on its direct URL) and all sign-up interaction is frozen, but it can be re-published.
            [
                'creator' => 8025,
                'status' => RevisionStatus::Approved,
                'unpublished' => true,
                'beginTime' => '+4 weeks 19:00',
                'endTime' => '+4 weeks 22:00',
                'category' => ActivityCategories::Other,
                'requireGEFLITST' => false,
                'requireZettle' => false,
                'name' => [
                    'en' => 'Unpublished Workshop',
                    'nl' => 'Gedepubliceerde Workshop',
                ],
                'location' => [
                    'en' => 'Lecture Room',
                    'nl' => 'Collegezaal',
                ],
                'costs' => [
                    'en' => 'Free',
                    'nl' => 'Gratis',
                ],
                'description' => [
                    'en' => 'This workshop is being reworked and is temporarily not public.',
                    'nl' => 'Deze workshop wordt herzien en is tijdelijk niet openbaar.',
                ],
                'signupLists' => [
                    [
                        'name' => [
                            'en' => 'Attendance',
                            'nl' => 'Aanwezigheid',
                        ],
                        'openDate' => '-1 day 12:00',
                        'closeDate' => '+2 weeks 18:00',
                        'onlyGEWIS' => true,
                        'displaySubscribedNumber' => true,
                        'limitedCapacity' => false,
                        'subscribers' => [
                            8007,
                        ],
                    ],
                ],
            ],
        ];

        foreach ($activities as $data) {
            $creator = $this->getReference(
                'member-' . $data['creator'],
                Member::class,
            );

            $activity = new Activity();
            $activity->setCreator($creator);

            // A seeded activity is a single-revision chain: revision 1 carries the content and its lifecycle state.
            $revision = $this->buildRevision(
                $data,
                $data['status'],
                $creator,
                1,
                null,
            );
            $revision->setRequireGEFLITST($data['requireGEFLITST']);
            $revision->setRequireZettle($data['requireZettle']);

            foreach ($data['labels'] ?? [] as $labelReference) {
                $revision->addLabel($this->getReference($labelReference, ActivityLabel::class));
            }

            $activity->addRevision($revision);
            $activity->setCurrentRevision($revision);

            if (RevisionStatus::Approved === $data['status']) {
                $activity->setLiveRevision($revision);
            }

            // Board lifecycle actions on an approved activity: cancel (stays public with a notice) or unpublish
            // (removed from public view). Both freeze sign-up interaction; the creator stands in as the board member.
            if ($data['cancelled'] ?? false) {
                $activity->cancel($creator);
            }

            if ($data['unpublished'] ?? false) {
                $activity->unpublish($creator);
            }

            $manager->persist($activity);
            $manager->persist($revision);

            foreach ($data['signupLists'] ?? [] as $signupListData) {
                $signupList = $this->createSignupList($signupListData);
                $revision->addSignupList($signupList);
                $manager->persist($signupList);

                // Sign-up fields (and their options for Choice fields), keyed by English name so a subscriber's
                // answers can reference them. Fields/options cascade-persist through the list.
                $fields = [];
                foreach ($signupListData['fields'] ?? [] as $fieldData) {
                    $field = new SignupField();
                    $field->setName(new ActivityLocalisedText($fieldData['name']['en'], $fieldData['name']['nl']));
                    $field->setType($fieldData['type']);
                    $signupList->addField($field);

                    $options = [];
                    foreach ($fieldData['options'] ?? [] as $optionData) {
                        $option = new SignupOption();
                        $option->setValue(new ActivityLocalisedText($optionData['en'], $optionData['nl']));
                        $field->addOption($option);
                        $options[$optionData['en']] = $option;
                    }

                    $fields[$fieldData['name']['en']] = [
                        'field' => $field,
                        'options' => $options,
                    ];
                }

                foreach ($signupListData['subscribers'] ?? [] as $subscriber) {
                    // A subscriber is either a bare lidnr or a richer array with presence and field answers.
                    $entry = is_array($subscriber)
                        ? $subscriber
                        : ['member' => $subscriber];

                    $signup = new UserSignup();
                    $signup->setSignupList($signupList);
                    $signup->setUser($this->getReference('member-' . $entry['member'], Member::class));
                    // A sign-up to a limited list starts on the waiting list (not drawn) until the organiser admits it;
                    // a sign-up to an unlimited list is admitted automatically. The future public subscribe flow MUST
                    // apply this same rule (drawn = !limitedCapacity) when it creates sign-ups.
                    $signup->setDrawn($entry['drawn'] ?? !$signupList->getLimitedCapacity());
                    $signup->setPresent($entry['present'] ?? false);
                    $manager->persist($signup);

                    $this->addFieldAnswers(
                        $signup,
                        $fields,
                        $entry['answers'] ?? [],
                        $manager,
                    );
                }

                foreach ($signupListData['externals'] ?? [] as $external) {
                    $signup = new ExternalSignup();
                    $signup->setSignupList($signupList);
                    $signup->setFullName($external['fullName']);
                    $signup->setEmail($external['email']);
                    // No token rows are seeded, so seeded externals are confirmed subscribers, mirroring the
                    // organiser-add path; without the stamp they would count as unverified everywhere.
                    $signup->setVerifiedAt(new DateTime());
                    $signup->setDrawn($external['drawn']);
                    $signup->setPresent($external['present']);
                    $manager->persist($signup);

                    $this->addFieldAnswers(
                        $signup,
                        $fields,
                        $external['answers'],
                        $manager,
                    );
                }
            }
        }

        $this->loadWorkflowExamples($manager);

        $manager->flush();
    }

    /**
     * Seeds activities that exercise the revision workflow: one awaiting review, one bounced back with a
     * changes-requested chain and a discussion thread, and one rejected with reviewer feedback.
     */
    private function loadWorkflowExamples(ObjectManager $manager): void
    {
        $boardA = $this->getReference(
            'member-8025',
            Member::class,
        );
        $boardB = $this->getReference(
            'member-8026',
            Member::class,
        );

        // The workflow examples are organised by an organ, so organ-scoped visibility/edit rights have something to
        // resolve against. GETÉST and KEUR have disjoint members, so the two can be told apart.
        $getest = $this->getReference(
            'organ-getest',
            Organ::class,
        );
        $keur = $this->getReference(
            'organ-keur',
            Organ::class,
        );

        // In review: sits in the board's review queue (no live revision, so not publicly visible).
        $hackathonCreator = $this->getReference(
            'member-8013',
            Member::class,
        );
        $hackathon = new Activity();
        $hackathon->setCreator($hackathonCreator);
        $hackathonRevision = $this->buildRevision(
            [
                'name' => [
                    'en' => 'Hackathon',
                    'nl' => 'Hackathon',
                ],
                'location' => [
                    'en' => 'MetaForum',
                    'nl' => 'MetaForum',
                ],
                'costs' => [
                    'en' => 'Free',
                    'nl' => 'Gratis',
                ],
                'description' => [
                    'en' => 'A 24-hour hackathon.',
                    'nl' => 'Een 24-uurs hackathon.',
                ],
                'beginTime' => '+8 weeks 18:00',
                'endTime' => '+8 weeks +1 day 18:00',
                'category' => ActivityCategories::Competition,
            ],
            RevisionStatus::InReview,
            $hackathonCreator,
            1,
            null,
        );
        $hackathonRevision->setOrgan($getest);
        $hackathon->addRevision($hackathonRevision);
        $hackathon->setCurrentRevision($hackathonRevision);
        $manager->persist($hackathon);
        $manager->persist($hackathonRevision);

        // Changes requested: revision 1 is an immutable record with a discussion thread; revision 2 (draft) continues.
        $beerCreator = $this->getReference(
            'member-8010',
            Member::class,
        );
        $beer = new Activity();
        $beer->setCreator($beerCreator);
        $beerRevision1 = $this->buildRevision(
            [
                'name' => [
                    'en' => 'Beer Tasting',
                    'nl' => 'Bierproeverij',
                ],
                'location' => [
                    'en' => 'Common Room',
                    'nl' => 'Huiskamer',
                ],
                'costs' => [
                    'en' => '',
                    'nl' => '',
                ],
                'description' => [
                    'en' => 'Tasting of local beers.',
                    'nl' => 'Proeverij van lokale bieren.',
                ],
                'beginTime' => '+4 weeks 20:00',
                'endTime' => '+4 weeks 23:00',
                'category' => ActivityCategories::SocialDrink,
            ],
            RevisionStatus::ChangesRequested,
            $beerCreator,
            1,
            null,
        );
        $beerRevision1->setReviewer($boardA);
        $beerRevision1->setReviewedAt(new DateTime('-2 days'));
        $beerRevision1->setOrgan($getest);
        $beer->addRevision($beerRevision1);
        $manager->persist($beer);
        $manager->persist($beerRevision1);
        $this->comment(
            $beerRevision1,
            $boardA,
            'Please add the price and confirm the location is booked.',
            $manager,
        );
        $this->comment(
            $beerRevision1,
            $beerCreator,
            'Updated the details — the room is booked and it is free for members.',
            $manager,
        );
        $beerRevision2 = $this->buildRevision(
            [
                'name' => [
                    'en' => 'Beer Tasting',
                    'nl' => 'Bierproeverij',
                ],
                'location' => [
                    'en' => 'Common Room (booked)',
                    'nl' => 'Huiskamer (geboekt)',
                ],
                'costs' => [
                    'en' => 'Free for members',
                    'nl' => 'Gratis voor leden',
                ],
                'description' => [
                    'en' => 'Tasting of local beers.',
                    'nl' => 'Proeverij van lokale bieren.',
                ],
                'beginTime' => '+4 weeks 20:00',
                'endTime' => '+4 weeks 23:00',
                'category' => ActivityCategories::SocialDrink,
            ],
            RevisionStatus::Draft,
            $beerCreator,
            2,
            $beerRevision1,
        );
        $beerRevision2->setOrgan($getest);
        $beer->addRevision($beerRevision2);
        $beer->setCurrentRevision($beerRevision2);
        $manager->persist($beerRevision2);

        // Rejected, with reviewer feedback.
        $casinoCreator = $this->getReference(
            'member-8012',
            Member::class,
        );
        $casino = new Activity();
        $casino->setCreator($casinoCreator);
        $casinoRevision = $this->buildRevision(
            [
                'name' => [
                    'en' => 'Casino Night',
                    'nl' => 'Casinoavond',
                ],
                'location' => [
                    'en' => 'Association Room',
                    'nl' => 'Verenigingskamer',
                ],
                'costs' => [
                    'en' => '10 euro',
                    'nl' => '10 euro',
                ],
                'description' => [
                    'en' => 'An evening of card games.',
                    'nl' => 'Een avond vol kaartspellen.',
                ],
                'beginTime' => '+5 weeks 20:00',
                'endTime' => '+5 weeks 23:30',
                'category' => ActivityCategories::Recreational,
            ],
            RevisionStatus::Rejected,
            $casinoCreator,
            1,
            null,
        );
        $casinoRevision->setReviewer($boardB);
        $casinoRevision->setReviewedAt(new DateTime('-5 days'));
        // KEUR (disjoint from GETÉST) so organ scoping can be told apart between the two organs.
        $casinoRevision->setOrgan($keur);
        $casino->addRevision($casinoRevision);
        $casino->setCurrentRevision($casinoRevision);
        $manager->persist($casino);
        $manager->persist($casinoRevision);
        $this->comment(
            $casinoRevision,
            $boardB,
            'Gambling activities are not permitted; please propose an alternative.',
            $manager,
        );
    }

    /**
     * @psalm-param array{
     *     name: array{en: string, nl: string},
     *     location: array{en: string, nl: string},
     *     costs: array{en: string, nl: string},
     *     description: array{en: string, nl: string},
     *     beginTime: string,
     *     endTime: string,
     *     category: ActivityCategories,
     *     ...<string, mixed>,
     * } $content the activity content; the main loop passes a wider row (with creator, labels, sign-up lists, …)
     */
    private function buildRevision(
        array $content,
        RevisionStatus $status,
        Member $author,
        int $revisionNumber,
        ?ActivityRevision $previous,
    ): ActivityRevision {
        $revision = new ActivityRevision();
        $revision->setAuthor($author);
        $revision->setStatus($status);
        $revision->setRevisionNumber($revisionNumber);
        $revision->setPreviousRevision($previous);
        $revision->setName(new ActivityLocalisedText($content['name']['en'], $content['name']['nl']));
        $revision->setLocation(new ActivityLocalisedText($content['location']['en'], $content['location']['nl']));
        $revision->setCosts(new ActivityLocalisedText($content['costs']['en'], $content['costs']['nl']));
        $revision->setDescription(
            new ActivityLocalisedText(
                $content['description']['en'],
                $content['description']['nl'],
            ),
        );
        $revision->setBeginTime(new DateTime($content['beginTime']));
        $revision->setEndTime(new DateTime($content['endTime']));
        $revision->setCategory($content['category']);

        return $revision;
    }

    private function comment(
        ActivityRevision $revision,
        Member $author,
        string $body,
        ObjectManager $manager,
    ): void {
        $comment = new ActivityRevisionComment();
        $comment->setRevision($revision);
        // The comment author is the member's user account (a CompanyUser would author careers comments); the board
        // members who comment all have a seeded user.
        $comment->setAuthor($this->getReference('user-' . $author->getLidnr(), User::class));
        $comment->setBody($body);
        $manager->persist($comment);
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
     *     capacity?: int,
     *     allocationMethod?: AllocationMethod,
     *     drawCutoffRule?: DrawCutoffRule,
     *     externalPolicyUrl?: string,
     *     customMethodDescription?: string,
     *     drawnAt?: string,
     *     drawnBy?: int,
     *     promoted?: bool,
     *     presenceTaken?: bool,
     *     fields?: list<array<string, mixed>>,
     *     subscribers?: list<int|array<string, mixed>>,
     *     externals?: list<array<string, mixed>>,
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
        $signupList->setCapacity($data['capacity'] ?? null);
        $signupList->setAllocationMethod($data['allocationMethod'] ?? AllocationMethod::FirstComeFirstServed);
        $signupList->setDrawCutoffRule($data['drawCutoffRule'] ?? null);
        $signupList->setExternalPolicyUrl($data['externalPolicyUrl'] ?? null);
        $signupList->setCustomMethodDescription($data['customMethodDescription'] ?? null);
        $signupList->setPromoted($data['promoted'] ?? false);
        $signupList->setPresenceTaken($data['presenceTaken'] ?? false);

        // A list that has already been drawn carries its lock + audit (a board member, by lidnr).
        if (isset($data['drawnAt'], $data['drawnBy'])) {
            $signupList->setDrawnAt(new DateTime($data['drawnAt']));
            $signupList->setDrawnBy($this->getReference('member-' . $data['drawnBy'], Member::class));
        }

        return $signupList;
    }

    /**
     * Attach a sign-up's answers to the given fields. Choice answers reference an option by its English value; every
     * other type stores the raw string.
     *
     * @param array<string, array{field: SignupField, options: array<string, SignupOption>}> $fields  by field name
     * @param array<string, string>                                                          $answers by field name
     */
    private function addFieldAnswers(
        Signup $signup,
        array $fields,
        array $answers,
        ObjectManager $manager,
    ): void {
        foreach ($answers as $fieldName => $answer) {
            if (!isset($fields[$fieldName])) {
                continue;
            }

            $field = $fields[$fieldName]['field'];
            $fieldValue = new SignupFieldValue();
            $fieldValue->setSignup($signup);
            $fieldValue->setField($field);

            if (SignupFieldTypes::Choice === $field->getType()) {
                $fieldValue->setOption($fields[$fieldName]['options'][$answer] ?? null);
            } else {
                $fieldValue->setValue($answer);
            }

            $manager->persist($fieldValue);
        }
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
            UserFixture::class,
            // The workflow examples are assigned an organising organ, so the organs must be seeded first.
            DecisionFixture::class,
        ];
    }
}
