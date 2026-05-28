<?php

declare(strict_types=1);

namespace App\DataFixtures\Activity;

use App\Entity\Activity\ActivityLabel;
use App\Entity\Activity\ActivityLocalisedText;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Override;

class ActivityLabelFixture extends Fixture
{
    public const string REFERENCE_FIRST_YEAR = 'activity-label-first-year';
    public const string REFERENCE_BACHELOR = 'activity-label-bachelor';
    public const string REFERENCE_MASTER = 'activity-label-master';
    public const string REFERENCE_PHD = 'activity-label-phd';
    public const string REFERENCE_DUTCH_ONLY = 'activity-label-dutch-only';
    public const string REFERENCE_ENGLISH_ONLY = 'activity-label-english-only';
    public const string REFERENCE_EXTERNALS = 'activity-label-externals';

    #[Override]
    public function load(ObjectManager $manager): void
    {
        // Labels describe the audience or language of an activity, never its type (a meeting, drink, party, ...).
        // The reference keys let other fixtures (e.g. ActivityFixture) attach labels to activities.
        $labels = [
            self::REFERENCE_FIRST_YEAR => [
                'en' => 'Useful for first-year students',
                'nl' => 'Nuttig voor eerstejaars',
            ],
            self::REFERENCE_BACHELOR => [
                'en' => 'Useful for bachelor students',
                'nl' => 'Nuttig voor bachelorstudenten',
            ],
            self::REFERENCE_MASTER => [
                'en' => 'Useful for master students',
                'nl' => 'Nuttig voor masterstudenten',
            ],
            self::REFERENCE_PHD => [
                'en' => 'Useful for PhD candidates',
                'nl' => 'Nuttig voor promovendi',
            ],
            self::REFERENCE_DUTCH_ONLY => [
                'en' => 'Dutch-only',
                'nl' => 'Alleen Nederlands',
            ],
            self::REFERENCE_ENGLISH_ONLY => [
                'en' => 'English-only',
                'nl' => 'Alleen Engels',
            ],
            self::REFERENCE_EXTERNALS => [
                'en' => 'Open to externals',
                'nl' => 'Open voor externen',
            ],
        ];

        foreach ($labels as $reference => $data) {
            $label = new ActivityLabel();
            $label->setName(new ActivityLocalisedText($data['en'], $data['nl']));

            $manager->persist($label);
            $this->addReference(
                $reference,
                $label,
            );
        }

        $manager->flush();
    }
}
