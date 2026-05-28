<?php

declare(strict_types=1);

namespace App\DataFixtures\Activity;

use App\Entity\Activity\ActivityCategory;
use App\Entity\Activity\ActivityLocalisedText;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Override;

class ActivityCategoryFixture extends Fixture
{
    public const string REFERENCE_FIRST_YEAR = 'activity-category-first-year';
    public const string REFERENCE_BACHELOR = 'activity-category-bachelor';
    public const string REFERENCE_MASTER = 'activity-category-master';
    public const string REFERENCE_PHD = 'activity-category-phd';
    public const string REFERENCE_DUTCH_ONLY = 'activity-category-dutch-only';
    public const string REFERENCE_ENGLISH_ONLY = 'activity-category-english-only';
    public const string REFERENCE_EXTERNALS = 'activity-category-externals';

    #[Override]
    public function load(ObjectManager $manager): void
    {
        // Categories describe the audience or language of an activity, never its type (a meeting, drink, party, ...).
        // The reference keys let other fixtures (e.g. ActivityFixture) attach categories to activities.
        $categories = [
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

        foreach ($categories as $reference => $data) {
            $category = new ActivityCategory();
            $category->setName(new ActivityLocalisedText($data['en'], $data['nl']));

            $manager->persist($category);
            $this->addReference(
                $reference,
                $category,
            );
        }

        $manager->flush();
    }
}
