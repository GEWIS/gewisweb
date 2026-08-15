<?php

declare(strict_types=1);

namespace App\DataFixtures\Frontpage;

use App\Entity\Frontpage\Enums\NewsCategory;
use App\Entity\Frontpage\NewsItem;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Override;

/**
 * A news feed with something in every category and two items pinned, so the front page, the filter chips and the
 * archive all have something to show.
 */
class NewsItemFixture extends Fixture
{
    #[Override]
    public function load(ObjectManager $manager): void
    {
        $items = [
            [
                'days' => 2,
                'category' => NewsCategory::Board,
                'pinned' => true,
                'titleEN' => 'Introduction week: sign-ups open for parents and mentors',
                'titleNL' => 'Introductieweek: aanmeldingen open voor ouders en mentoren',
                'contentEN' => 'The intro committee has opened the parent subscription and the mentor list for the '
                    . "coming introduction week.\n\nSpots for mentors are limited and are handed out in the order they "
                    . 'come in, so do not leave it until the last day.',
                'contentNL' => 'De introcommissie heeft de ouderinschrijving en de mentorlijst voor de komende '
                    . "introductieweek geopend.\n\nHet aantal mentorplekken is beperkt en wordt op volgorde van "
                    . 'binnenkomst verdeeld, dus wacht niet tot de laatste dag.',
            ],
            [
                'days' => 5,
                'category' => NewsCategory::Career,
                'pinned' => true,
                'titleEN' => 'Career day: 34 companies confirmed',
                'titleNL' => 'Carrièredag: 34 bedrijven bevestigd',
                'contentEN' => 'The largest edition so far. Book a one-on-one slot with a company before the list '
                    . 'closes, and bring your CV for the free review desk.',
                'contentNL' => 'De grootste editie tot nu toe. Reserveer een gesprek met een bedrijf voordat de lijst '
                    . 'sluit, en neem je cv mee voor de gratis cv-check.',
            ],
            [
                'days' => 9,
                'category' => NewsCategory::Association,
                'pinned' => false,
                'titleEN' => 'The yearbook can be picked up in the association room',
                'titleNL' => 'Het jaarboek kan worden opgehaald in de GEWIS-ruimte',
                'contentEN' => 'The print run has arrived. Come by between 12:00 and 17:00 with your member card. One '
                    . 'copy per member; what is left goes to the archive.',
                'contentNL' => 'De oplage is binnen. Kom langs tussen 12:00 en 17:00 met je lidmaatschapskaart. Eén '
                    . 'exemplaar per lid; wat overblijft gaat naar het archief.',
            ],
            [
                'days' => 14,
                'category' => NewsCategory::Education,
                'pinned' => false,
                'titleEN' => 'Summary bundles for this quartile are online',
                'titleNL' => 'Samenvattingenbundels voor dit kwartiel staan online',
                'contentEN' => 'Twenty-two courses are covered this quartile. Missing your course? Send your notes to '
                    . 'the education committee and it will be added.',
                'contentNL' => 'Dit kwartiel zijn tweeëntwintig vakken gedekt. Staat jouw vak er niet bij? Stuur je '
                    . 'aantekeningen naar de onderwijscommissie, dan wordt het toegevoegd.',
            ],
            [
                'days' => 21,
                'category' => NewsCategory::Committees,
                'pinned' => false,
                'titleEN' => 'Two hundred photos of the symposium are online',
                'titleNL' => 'Tweehonderd foto\'s van het symposium staan online',
                'contentEN' => 'The full album is available to members. Tag yourself, or ask for a photo to be taken '
                    . 'down through the photo page if you would rather not appear.',
                'contentNL' => 'Het volledige album is beschikbaar voor leden. Tag jezelf, of vraag via de fotopagina '
                    . 'om een foto weg te halen als je er liever niet op staat.',
            ],
            [
                'days' => 30,
                'category' => NewsCategory::Board,
                'pinned' => false,
                'titleEN' => 'Minutes of the general members meeting',
                'titleNL' => 'Notulen van de algemene ledenvergadering',
                'contentEN' => 'The budget for the coming association year was approved and two candidate board '
                    . 'members were introduced. The full minutes and the financial statement are in the archive.',
                'contentNL' => 'De begroting voor het komende verenigingsjaar is goedgekeurd en twee kandidaat-'
                    . 'bestuursleden zijn voorgesteld. De volledige notulen en de jaarrekening staan in het archief.',
            ],
        ];

        foreach ($items as $item) {
            $news = new NewsItem();
            $news->setDate(new DateTime('-' . $item['days'] . ' days'));
            $news->setCategory($item['category']);
            $news->setPinned($item['pinned']);
            $news->setEnglishTitle($item['titleEN']);
            $news->setDutchTitle($item['titleNL']);
            $news->setEnglishContent($item['contentEN']);
            $news->setDutchContent($item['contentNL']);

            $manager->persist($news);
        }

        $manager->flush();
    }
}
