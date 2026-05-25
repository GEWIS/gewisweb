<?php

declare(strict_types=1);

namespace App\DataFixtures\Frontpage;

use App\Entity\Frontpage\FrontpageLocalisedText;
use App\Entity\Frontpage\Page;
use App\Entity\User\Enums\UserRoles;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Override;

class PageFixture extends Fixture
{
    #[Override]
    public function load(ObjectManager $manager): void
    {
        // This creates some of the default pages that exist in the main navbar.
        $pages = [
            '/association' => [
                'category' => [
                    'en' => 'association',
                    'nl' => 'vereniging',
                ],
                'subCategory' => [
                    'en' => null,
                    'nl' => null,
                ],
                'name' => [
                    'en' => null,
                    'nl' => null,
                ],
                'title' => [
                    'en' => 'About the Association',
                    'nl' => 'Over de Vereniging',
                ],
                'content' => [
                    'en' => '<p>Welcome to the association page.</p>',
                    'nl' => '<p>Welkom op de verenigingspagina.</p>',
                ],
            ],
            '/association/board' => [
                'category' => [
                    'en' => 'association',
                    'nl' => 'vereniging',
                ],
                'subCategory' => [
                    'en' => 'board',
                    'nl' => 'bestuur',
                ],
                'name' => [
                    'en' => null,
                    'nl' => null,
                ],
                'title' => [
                    'en' => 'Board Members',
                    'nl' => 'Bestuursleden',
                ],
                'content' => [
                    'en' => '<p>Meet our board members.</p>',
                    'nl' => '<p>Ontmoet onze bestuursleden.</p>',
                ],
            ],
            '/association/exceptional-members' => [
                'category' => [
                    'en' => 'association',
                    'nl' => 'vereniging',
                ],
                'subCategory' => [
                    'en' => 'exceptional-members',
                    'nl' => 'uitzonderlijke-leden',
                ],
                'name' => [
                    'en' => null,
                    'nl' => null,
                ],
                'title' => [
                    'en' => 'Exceptional Members',
                    'nl' => 'Uitzonderlijke Leden',
                ],
                'content' => [
                    'en' => '<p>Learn about our exceptional members.</p>',
                    'nl' => '<p>Lees meer over onze uitzonderlijke leden.</p>',
                ],
            ],
            '/association/song' => [
                'category' => [
                    'en' => 'association',
                    'nl' => 'vereniging',
                ],
                'subCategory' => [
                    'en' => 'song',
                    'nl' => 'lied',
                ],
                'name' => [
                    'en' => null,
                    'nl' => null,
                ],
                'title' => [
                    'en' => 'Association Song',
                    'nl' => 'Verenigingslied',
                ],
                'content' => [
                    'en' => '<p>Here is our association song.</p>',
                    'nl' => '<p>Hier is ons verenigingslied.</p>',
                ],
            ],
            '/association/regulations' => [
                'category' => [
                    'en' => 'association',
                    'nl' => 'vereniging',
                ],
                'subCategory' => [
                    'en' => 'regulations',
                    'nl' => 'reglementen',
                ],
                'name' => [
                    'en' => null,
                    'nl' => null,
                ],
                'title' => [
                    'en' => 'Association Regulations',
                    'nl' => 'Verenigingsreglementen',
                ],
                'content' => [
                    'en' => '<p>Read our regulations.</p>',
                    'nl' => '<p>Lees onze reglementen.</p>',
                ],
            ],
            '/association/contact' => [
                'category' => [
                    'en' => 'association',
                    'nl' => 'vereniging',
                ],
                'subCategory' => [
                    'en' => 'contact',
                    'nl' => 'contact',
                ],
                'name' => [
                    'en' => null,
                    'nl' => null,
                ],
                'title' => [
                    'en' => 'Contact Us',
                    'nl' => 'Neem Contact Op',
                ],
                'content' => [
                    'en' => '<p>Get in touch with us.</p>',
                    'nl' => '<p>Neem contact met ons op.</p>',
                ],
            ],
        ];

        foreach ($pages as $data) {
            $page = new Page();
            $page->setCategory(new FrontpageLocalisedText($data['category']['en'], $data['category']['nl']));
            $page->setSubCategory(new FrontpageLocalisedText($data['subCategory']['en'], $data['subCategory']['nl']));
            $page->setName(new FrontpageLocalisedText($data['name']['en'], $data['name']['nl']));
            $page->setTitle(new FrontpageLocalisedText($data['title']['en'], $data['title']['nl']));
            $page->setContent(new FrontpageLocalisedText($data['content']['en'], $data['content']['nl']));
            $page->setRequiredRole(UserRoles::Guest);

            $manager->persist($page);
        }

        $manager->flush();
    }
}
