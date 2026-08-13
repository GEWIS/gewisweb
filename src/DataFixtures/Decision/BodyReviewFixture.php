<?php

declare(strict_types=1);

namespace App\DataFixtures\Decision;

use App\DataFixtures\User\UserFixture;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Application\Enums\SocialPlatform;
use App\Entity\Decision\DecisionLocalisedText;
use App\Entity\Decision\Member as MemberModel;
use App\Entity\Decision\OrganInformation;
use App\Entity\Decision\OrganInformationRevision;
use App\Entity\Decision\OrganInformationRevisionComment;
use App\Entity\User\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

/**
 * Leaves one body waiting on the board, so the review queue and the review screen have something to show without
 * anybody having to click a page through the workflow by hand. The submitted revision changes text, drops a social link
 * and adds another, which is what makes the comparison worth looking at.
 *
 * Statuses are set directly rather than driven through the state machine, for the same reason
 * {@see OrganInformationFixture} does.
 */
class BodyReviewFixture extends Fixture implements DependentFixtureInterface
{
    public function __construct(private readonly OrganImageGenerator $imageGenerator)
    {
    }

    #[Override]
    public function load(ObjectManager $manager): void
    {
        $information = $this->getReference(
            'organ-information-keur',
            OrganInformation::class,
        );
        $live = $information->getLiveRevision();
        if (null === $live) {
            return;
        }

        $author = $this->getReference(
            'member-8001',
            MemberModel::class,
        );

        $draft = new OrganInformationRevision();
        $draft->setStatus(RevisionStatus::Submitted);
        $draft->setRevisionNumber(2);
        $draft->setPreviousRevision($live);
        $draft->setAuthor($author);
        $draft->setShortDescription(new DecisionLocalisedText(
            'Three members who decide what is good enough, and say why.',
            'Drie leden die bepalen wat goed genoeg is, en uitleggen waarom.',
        ));
        $draft->setDescription(new DecisionLocalisedText(
            'KEUR looks at what the association produces and says whether it passes. We publish our findings after '
            . 'every round, together with the reasoning behind them.',
            'KEUR bekijkt wat de vereniging maakt en zegt of het door de keuring komt. Na elke ronde publiceren we '
            . 'onze bevindingen, samen met de onderbouwing.',
        ));
        $draft->setEmail($live->getEmail());
        $draft->setWebsite('https://keur.gewis.nl');
        $draft->setBannerSource($live->getBannerSource());
        $draft->setBannerCrop($live->getBannerCrop());
        $draft->setBannerPath($live->getBannerPath());

        // A different card image, so the board has two of them to look at side by side.
        $thumbnail = $this->imageGenerator->storeLogo('KEUR 2');
        $draft->setLogoSource($thumbnail);
        $draft->setLogoPath($thumbnail);
        $draft->setLogoCrop($live->getLogoCrop());

        // The invite code is gone and an Instagram account has appeared, which is the kind of change the section exists
        // to show.
        $draft->updateSocialLinks([SocialPlatform::Instagram->value => 'keurgewis']);

        $information->addRevision($draft);
        $information->setCurrentRevision($draft);

        $manager->persist($draft);

        $comment = new OrganInformationRevisionComment();
        $comment->attachTo($draft);
        $comment->setAuthor($this->getReference(
            'user-8001',
            User::class,
        ));
        $comment->setBody('We rewrote the description and moved from Discord to Instagram.');
        $manager->persist($comment);

        $manager->flush();
    }

    /**
     * @return class-string<Fixture>[]
     */
    #[Override]
    public function getDependencies(): array
    {
        return [
            OrganInformationFixture::class,
            UserFixture::class,
        ];
    }
}
