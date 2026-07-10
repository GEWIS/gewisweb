<?php

declare(strict_types=1);

namespace App\DataFixtures\Career;

use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Career\CareerLocalisedText;
use App\Entity\Career\Company;
use App\Entity\Career\CompanyBannerPackage;
use App\Entity\Career\CompanyFeaturedPackage;
use App\Entity\Career\CompanyJobPackage;
use App\Entity\Career\CompanyPackage;
use App\Entity\Career\CompanyRevision;
use App\Entity\Career\Enums\VacancyCategories;
use App\Entity\Career\Vacancy;
use App\Entity\Career\VacancyLabel;
use App\Entity\Career\VacancyRevision;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Override;

/**
 * Seeds a handful of publicly visible companies with active packages and vacancies across the vacancy categories, so
 * the career overview and the per-category vacancy listings have something to show.
 *
 * @psalm-type VacancyData = array{
 *     slug: string,
 *     category: VacancyCategories,
 *     nameEn: string,
 *     nameNl: string,
 *     descriptionEn: string,
 *     descriptionNl: string,
 *     labels: string[],
 * }
 */
class CompanyFixture extends Fixture
{
    /** @var array<string, VacancyLabel> */
    private array $labels = [];

    #[Override]
    public function load(ObjectManager $manager): void
    {
        $this->createLabels($manager);

        $this->createCompany(
            $manager,
            slug: 'nexunt',
            name: 'Nexunt Systems',
            sloganEn: 'Building the backbone of tomorrow',
            sloganNl: 'De ruggengraat van morgen bouwen',
            descriptionEn: 'Nexunt Systems designs high-availability infrastructure for research institutions.',
            descriptionNl: 'Nexunt Systems ontwerpt hoogbeschikbare infrastructuur voor onderzoeksinstellingen.',
            websiteEn: 'https://example.com/nexunt',
            websiteNl: 'https://example.com/nexunt',
            featured: true,
            banner: false,
            vacancies: [
                [
                    'slug' => 'backend-engineer',
                    'category' => VacancyCategories::Jobs,
                    'nameEn' => 'Backend Engineer',
                    'nameNl' => 'Backend Engineer',
                    // Long, multi-paragraph markdown (heading + bullet list) to stress a tall card.
                    'descriptionEn' => "## About the role\n\n"
                        . 'You will design and operate the high-availability services that our research customers '
                        . "depend on around the clock. Expect real ownership from day one.\n\n"
                        . "**What you will do:**\n\n"
                        . "- Own critical backend services from design through production\n"
                        . "- Improve our observability, alerting and on-call practices\n"
                        . "- Mentor interns and junior engineers on the platform team\n\n"
                        . 'We offer a stable, fully-funded team and plenty of room to grow towards architecture or '
                        . 'technical leadership over time.',
                    'descriptionNl' => "## Over de functie\n\n"
                        . 'Je ontwerpt en beheert de hoogbeschikbare diensten waar onze onderzoeksklanten dag en '
                        . "nacht op vertrouwen. Vanaf dag één krijg je echte verantwoordelijkheid.\n\n"
                        . "**Wat je gaat doen:**\n\n"
                        . "- Kritieke backend-diensten bezitten van ontwerp tot productie\n"
                        . "- Onze observability, alerting en on-call-praktijken verbeteren\n"
                        . "- Stagiairs en junior engineers in het platformteam begeleiden\n\n"
                        . 'We bieden een stabiel, volledig gefinancierd team en ruimte om door te groeien naar '
                        . 'architectuur of technisch leiderschap.',
                    'labels' => [
                        'fulltime',
                        'hybrid',
                    ],
                ],
                [
                    'slug' => 'platform-internship',
                    'category' => VacancyCategories::Internships,
                    'nameEn' => 'Platform Engineering Internship',
                    'nameNl' => 'Stage Platform Engineering',
                    // Deliberately very short, to contrast with the tall card above.
                    'descriptionEn' => 'Help us automate our deployment pipeline during a six-month internship.',
                    'descriptionNl' => 'Help onze deployment-pipeline te automatiseren in een stage van zes maanden.',
                    'labels' => ['hybrid'],
                ],
            ],
        );

        $this->createCompany(
            $manager,
            slug: 'orbit-analytics',
            name: 'Orbit Analytics',
            sloganEn: 'Turning data into decisions',
            sloganNl: 'Van data naar beslissingen',
            descriptionEn: 'Orbit Analytics is a data science consultancy for ambitious scale-ups.',
            descriptionNl: 'Orbit Analytics is een data science consultancy voor ambitieuze scale-ups.',
            websiteEn: 'https://example.com/orbit',
            websiteNl: 'https://example.com/orbit',
            featured: false,
            banner: true,
            vacancies: [
                [
                    'slug' => 'data-science-internship',
                    'category' => VacancyCategories::Internships,
                    'nameEn' => 'Data Science Internship',
                    'nameNl' => 'Stage Data Science',
                    // Medium: a single solid paragraph.
                    'descriptionEn' => 'Join our consultancy team for a semester and work on real client data. You '
                        . 'will build models, present findings to stakeholders, and learn how data science delivers '
                        . 'value in practice. A compensation and a laptop are provided.',
                    'descriptionNl' => 'Kom een semester bij ons consultancyteam en werk met echte klantdata. Je '
                        . 'bouwt modellen, presenteert bevindingen aan stakeholders en leert hoe data science in de '
                        . 'praktijk waarde levert. Een vergoeding en laptop worden geregeld.',
                    'labels' => ['remote'],
                ],
                [
                    'slug' => 'ml-thesis',
                    'category' => VacancyCategories::ThesisProjects,
                    'nameEn' => 'Master Thesis: Explainable ML',
                    'nameNl' => 'Afstudeerproject: Verklaarbare ML',
                    // Long-ish single paragraph (no markdown structure).
                    'descriptionEn' => 'We are looking for a graduate student to research explainability methods for '
                        . 'the models we deploy in production. You will have access to anonymised datasets, a '
                        . 'dedicated supervisor, and the opportunity to publish your results at the end of the '
                        . 'project. Strong Python skills and a background in machine learning are expected.',
                    'descriptionNl' => 'We zoeken een afstudeerder om verklaarbaarheidsmethoden te onderzoeken voor de '
                        . 'modellen die wij in productie draaien. Je krijgt toegang tot geanonimiseerde datasets, een '
                        . 'vaste begeleider en de kans om je resultaten aan het eind te publiceren. Sterke '
                        . 'Python-vaardigheden en een achtergrond in machine learning zijn vereist.',
                    'labels' => [],
                ],
            ],
        );

        $this->createCompany(
            $manager,
            slug: 'delta-robotics',
            name: 'Delta Robotics',
            sloganEn: 'Automation with a human touch',
            sloganNl: 'Automatisering met een menselijke maat',
            descriptionEn: 'Delta Robotics builds collaborative robots for small manufacturers across the country.',
            descriptionNl: 'Delta Robotics bouwt samenwerkende robots voor kleine fabrikanten door het hele land.',
            websiteEn: 'https://example.com/delta',
            websiteNl: 'https://example.com/delta',
            featured: false,
            banner: false,
            vacancies: [
                [
                    'slug' => 'robotics-traineeship',
                    'category' => VacancyCategories::Traineeships,
                    'nameEn' => 'Robotics Traineeship',
                    'nameNl' => 'Traineeship Robotica',
                    // Medium paragraph.
                    'descriptionEn' => 'A two-year traineeship rotating across our mechanical, software and controls '
                        . 'teams. Ideal for graduates who want broad exposure before specialising.',
                    'descriptionNl' => 'Een tweejarig traineeship langs onze mechanische, software- en controls-teams. '
                        . 'Ideaal voor afgestudeerden die eerst breed willen kijken voordat ze zich specialiseren.',
                    'labels' => ['fulltime'],
                ],
                [
                    'slug' => 'student-assistant',
                    'category' => VacancyCategories::StudentJobs,
                    'nameEn' => 'Student Assistant Robotics Lab',
                    'nameNl' => 'Studentassistent Roboticalab',
                    // Very short.
                    'descriptionEn' => 'Support our lab a few hours per week alongside your studies.',
                    'descriptionNl' => 'Ondersteun ons lab een paar uur per week naast je studie.',
                    'labels' => ['remote'],
                ],
                [
                    'slug' => 'controls-engineer',
                    'category' => VacancyCategories::Jobs,
                    'nameEn' => 'Controls Engineer',
                    'nameNl' => 'Controls Engineer',
                    // Medium with an inline bullet list.
                    'descriptionEn' => 'Design and tune the motion control that makes our cobots safe to work '
                        . "alongside.\n\n"
                        . "- Develop real-time control loops\n"
                        . "- Validate safety behaviour on the shop floor\n"
                        . '- Work directly with our manufacturing customers',
                    'descriptionNl' => 'Ontwerp en stem de bewegingsbesturing af die onze cobots veilig maakt om mee '
                        . "samen te werken.\n\n"
                        . "- Realtime regelkringen ontwikkelen\n"
                        . "- Veiligheidsgedrag op de werkvloer valideren\n"
                        . '- Direct samenwerken met onze productieklanten',
                    'labels' => [
                        'fulltime',
                        'hybrid',
                    ],
                ],
            ],
        );

        $manager->flush();
    }

    private function createLabels(ObjectManager $manager): void
    {
        $definitions = [
            'fulltime' => [
                'en' => 'Full-time',
                'nl' => 'Fulltime',
                'abbr' => 'FT',
            ],
            'hybrid' => [
                'en' => 'Hybrid',
                'nl' => 'Hybride',
                'abbr' => 'HYB',
            ],
            'remote' => [
                'en' => 'Remote',
                'nl' => 'Op afstand',
                'abbr' => 'REM',
            ],
        ];

        foreach ($definitions as $key => $definition) {
            $label = new VacancyLabel();
            $label->setName(new CareerLocalisedText($definition['en'], $definition['nl']));
            $label->setAbbreviation(new CareerLocalisedText($definition['abbr'], $definition['abbr']));
            $manager->persist($label);

            $this->labels[$key] = $label;
        }
    }

    /**
     * @param VacancyData[] $vacancies
     */
    private function createCompany(
        ObjectManager $manager,
        string $slug,
        string $name,
        string $sloganEn,
        string $sloganNl,
        string $descriptionEn,
        string $descriptionNl,
        string $websiteEn,
        string $websiteNl,
        bool $featured,
        bool $banner,
        array $vacancies,
    ): void {
        $company = new Company();
        $company->setName($name);
        $company->setSlugName($slug);
        $company->setRepresentativeName('Recruitment ' . $name);
        $company->setRepresentativeEmail('recruitment@' . $slug . '.example.com');
        $company->setPublished(true);

        $revision = new CompanyRevision();
        $revision->setStatus(RevisionStatus::Approved);
        $revision->setRevisionNumber(1);
        $revision->setSlogan(new CareerLocalisedText($sloganEn, $sloganNl));
        $revision->setDescription(new CareerLocalisedText($descriptionEn, $descriptionNl));
        $revision->setWebsite(new CareerLocalisedText($websiteEn, $websiteNl));
        $revision->setContactName('Recruitment Team');
        $revision->setContactEmail('recruitment@' . $slug . '.example.com');
        $revision->setContactPhone('+31 40 000 0000');

        $company->addRevision($revision);
        $company->setCurrentRevision($revision);
        $company->setLiveRevision($revision);

        // Exposed so other fixtures (e.g. activities organised by a company) can reference it.
        $this->addReference(
            'career-company-' . $slug,
            $company,
        );

        $jobPackage = new CompanyJobPackage();
        $this->configurePackage(
            $jobPackage,
            $company,
        );

        foreach ($vacancies as $data) {
            $vacancy = $this->createVacancy(
                $jobPackage,
                $data,
            );
            $manager->persist($vacancy);
        }

        $manager->persist($company);
        $manager->persist($revision);
        $manager->persist($jobPackage);

        if ($banner) {
            $bannerPackage = new CompanyBannerPackage();
            $this->configurePackage(
                $bannerPackage,
                $company,
            );
            $bannerPackage->setImage('data/company/banner/' . $slug . '.png');
            $manager->persist($bannerPackage);
        }

        if (!$featured) {
            return;
        }

        $featuredPackage = new CompanyFeaturedPackage();
        $this->configurePackage(
            $featuredPackage,
            $company,
        );
        $featuredPackage->setArticle(new CareerLocalisedText(
            'We sat down with ' . $name . ' to talk about the projects our members could work on.',
            'We spraken met ' . $name . ' over de projecten waaraan onze leden kunnen werken.',
        ));
        $manager->persist($featuredPackage);
    }

    /**
     * Makes a package active: started in the past, expiring far in the future, and published.
     */
    private function configurePackage(
        CompanyPackage $package,
        Company $company,
    ): void {
        $package->setCompany($company);
        $package->setStartingDate(new DateTime('2020-01-01'));
        $package->setExpirationDate(new DateTime('2100-01-01'));
        $package->setPublished(true);
    }

    /**
     * @param VacancyData $data
     */
    private function createVacancy(
        CompanyJobPackage $package,
        array $data,
    ): Vacancy {
        $vacancy = new Vacancy();
        $vacancy->setSlugName($data['slug']);
        $vacancy->setPublished(true);
        $vacancy->setPackage($package);
        $package->addVacancy($vacancy);

        $revision = new VacancyRevision();
        $revision->setStatus(RevisionStatus::Approved);
        $revision->setRevisionNumber(1);
        $revision->setName(new CareerLocalisedText($data['nameEn'], $data['nameNl']));
        $revision->setLocation(new CareerLocalisedText('Eindhoven', 'Eindhoven'));
        $revision->setWebsite(new CareerLocalisedText(
            'https://example.com/' . $data['slug'],
            'https://example.com/' . $data['slug'],
        ));
        $revision->setDescription(new CareerLocalisedText(
            $data['descriptionEn'],
            $data['descriptionNl'],
        ));
        $revision->setAttachment(new CareerLocalisedText('', ''));
        $revision->setCategory($data['category']);

        foreach ($data['labels'] as $labelKey) {
            $revision->addLabel($this->labels[$labelKey]);
        }

        $vacancy->addRevision($revision);
        $vacancy->setCurrentRevision($revision);
        $vacancy->setLiveRevision($revision);

        return $vacancy;
    }
}
