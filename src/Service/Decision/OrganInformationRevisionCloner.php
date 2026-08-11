<?php

declare(strict_types=1);

namespace App\Service\Decision;

use App\Entity\Application\AbstractRevision;
use App\Entity\Application\RevisionInterface;
use App\Entity\Decision\OrganInformationRevision;
use App\Workflow\AbstractRevisionCloner;
use Override;

use function assert;

/**
 * Spawns the next draft {@see OrganInformationRevision} from an existing one, whether because the board asked for
 * changes, because a rejected page is being reopened, or because a body wants to change what is already on the website.
 * The localised texts and the social links are deep-copied into fresh rows so orphan removal can never delete the
 * source revision's content; the images and the crops on them are carried by value.
 */
final readonly class OrganInformationRevisionCloner extends AbstractRevisionCloner
{
    #[Override]
    public function supports(RevisionInterface $revision): bool
    {
        return $revision instanceof OrganInformationRevision;
    }

    #[Override]
    protected function spawnDraft(RevisionInterface $source): OrganInformationRevision
    {
        assert($source instanceof OrganInformationRevision);

        $information = $source->getOrganInformation();

        $draft = new OrganInformationRevision();
        $draft->setPreviousRevision($source);
        $information->addRevision($draft);
        $information->setCurrentRevision($draft);

        return $draft;
    }

    #[Override]
    protected function copyContent(
        RevisionInterface $source,
        AbstractRevision $draft,
    ): void {
        assert($source instanceof OrganInformationRevision);
        assert($draft instanceof OrganInformationRevision);

        $draft->setShortDescription($source->getShortDescription()->copy());
        $draft->setDescription($source->getDescription()->copy());
        $draft->setEmail($source->getEmail());
        $draft->setWebsite($source->getWebsite());
        $draft->setBannerSource($source->getBannerSource());
        $draft->setBannerCrop($source->getBannerCrop());
        $draft->setBannerPath($source->getBannerPath());
        $draft->setLogoSource($source->getLogoSource());
        $draft->setLogoCrop($source->getLogoCrop());
        $draft->setLogoPath($source->getLogoPath());

        foreach ($source->getSocialLinks() as $link) {
            $copy = $link->copy();
            $copy->setRevision($draft);
            $draft->getSocialLinks()->add($copy);
        }
    }
}
