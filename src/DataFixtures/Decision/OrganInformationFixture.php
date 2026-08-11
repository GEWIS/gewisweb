<?php

declare(strict_types=1);

namespace App\DataFixtures\Decision;

use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Application\Enums\SocialPlatform;
use App\Entity\Decision\DecisionLocalisedText;
use App\Entity\Decision\Member as MemberModel;
use App\Entity\Decision\Organ;
use App\Entity\Decision\OrganInformation;
use App\Entity\Decision\OrganInformationRevision;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Override;
use Symfony\Component\String\Slugger\SluggerInterface;

use function strtolower;

/**
 * Gives the seeded bodies a page the website can show, so the overviews, the body pages and the cards have real text
 * and real images rather than an empty state everywhere.
 *
 * Statuses are set directly rather than driven through the state machine: the guards want an authenticated reviewer,
 * which a fixture has no business inventing, and what this seeds is the resulting state rather than the route to it.
 */
class OrganInformationFixture extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly OrganImageGenerator $imageGenerator,
        private readonly SluggerInterface $slugger,
    ) {
    }

    #[Override]
    public function load(ObjectManager $manager): void
    {
        $reviewer = $this->getReference(
            'member-8000',
            MemberModel::class,
        );

        $this->createPage(
            $manager,
            $this->getReference(
                'organ-getest',
                Organ::class,
            ),
            $reviewer,
            shortDescriptionEn: 'The committee that tests everything nobody else wants to.',
            shortDescriptionNl: 'De commissie die test wat niemand anders wil testen.',
            descriptionEn: "## What we do\n\n"
                . 'GETÉST goes through every corner of the association that somebody else built and checks that it '
                . "still works. Expect thoroughness, opinions and a great deal of coffee.\n\n"
                . "We meet every other week, and we are always looking for members who enjoy breaking things.\n",
            descriptionNl: "## Wat we doen\n\n"
                . 'GETÉST loopt elke hoek van de vereniging na die iemand anders heeft gebouwd en controleert of het '
                . "nog werkt. Verwacht grondigheid, meningen en heel veel koffie.\n\n"
                . "We vergaderen elke twee weken en zoeken altijd leden die graag dingen slopen.\n",
            website: 'https://getest.gewis.nl',
            social: [
                SocialPlatform::Instagram->value => 'getest',
                SocialPlatform::Mastodon->value => 'getest@mastodon.social',
            ],
        );

        $this->createPage(
            $manager,
            $this->getReference(
                'organ-keur',
                Organ::class,
            ),
            $reviewer,
            shortDescriptionEn: 'Three members who decide what is good enough.',
            shortDescriptionNl: 'Drie leden die bepalen wat goed genoeg is.',
            descriptionEn: 'KEUR looks at what the association produces and says whether it passes. We publish our '
                . 'findings after every round.',
            descriptionNl: 'KEUR bekijkt wat de vereniging maakt en zegt of het door de keuring komt. Na elke ronde '
                . 'publiceren we onze bevindingen.',
            website: null,
            social: [SocialPlatform::Discord->value => 'keurKEUR1'],
        );

        $manager->flush();
    }

    /**
     * @param array<string, string> $social
     */
    private function createPage(
        ObjectManager $manager,
        Organ $organ,
        MemberModel $reviewer,
        string $shortDescriptionEn,
        string $shortDescriptionNl,
        string $descriptionEn,
        string $descriptionNl,
        ?string $website,
        array $social,
    ): void {
        $abbr = $organ->getAbbr();

        $information = new OrganInformation();
        $information->setOrgan($organ);
        $organ->setOrganInformation($information);

        $revision = new OrganInformationRevision();
        $revision->setStatus(RevisionStatus::Approved);
        $revision->setRevisionNumber(1);
        $revision->setAuthor($reviewer);
        $revision->setReviewer($reviewer);
        $revision->setShortDescription(new DecisionLocalisedText(
            $shortDescriptionEn,
            $shortDescriptionNl,
        ));
        $revision->setDescription(new DecisionLocalisedText(
            $descriptionEn,
            $descriptionNl,
        ));
        // An abbreviation is a name, not an address: GETÉST carries an accent that the validator rejects in a local
        // part, which would make the seeded page unsaveable until somebody edited the field.
        $revision->setEmail($this->slugger->slug($abbr)->lower() . '@gewis.nl');
        $revision->setWebsite($website);
        $revision->updateSocialLinks($social);

        // The fixture artwork is drawn at the shape it is shown in, so the whole of it is the crop.
        $cover = $this->imageGenerator->storeBanner($abbr);
        $revision->setBannerSource($cover);
        $revision->setBannerPath($cover);
        $revision->setBannerCrop($this->wholeFrame());

        $thumbnail = $this->imageGenerator->storeLogo($abbr);
        $revision->setLogoSource($thumbnail);
        $revision->setLogoPath($thumbnail);
        $revision->setLogoCrop($this->wholeFrame());

        $information->addRevision($revision);
        $information->setCurrentRevision($revision);
        $information->setLiveRevision($revision);

        $manager->persist($information);
        $manager->persist($revision);

        $this->addReference(
            'organ-information-' . strtolower($abbr),
            $information,
        );
    }

    /**
     * @return array<string, float>
     */
    private function wholeFrame(): array
    {
        return [
            'x' => 0.0,
            'y' => 0.0,
            'width' => 1.0,
            'height' => 1.0,
        ];
    }

    /**
     * @return class-string<Fixture>[]
     */
    #[Override]
    public function getDependencies(): array
    {
        return [DecisionFixture::class];
    }
}
