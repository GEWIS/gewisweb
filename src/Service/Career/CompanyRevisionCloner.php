<?php

declare(strict_types=1);

namespace App\Service\Career;

use App\Entity\Application\AbstractRevision;
use App\Entity\Application\RevisionInterface;
use App\Entity\Career\CompanyRevision;
use App\Workflow\AbstractRevisionCloner;
use Override;

use function assert;

/**
 * Spawns the next Draft {@see CompanyRevision} from an existing one (for "changes requested", reopening, or editing
 * an approved company profile). The localised texts are deep-copied into fresh rows so orphan-removal can never delete
 * the source revision's content; the logo and contact details are copied by value. The shared workflow wiring
 * (authorship, revision number, chain link) lives in {@see AbstractRevisionCloner}.
 */
final readonly class CompanyRevisionCloner extends AbstractRevisionCloner
{
    #[Override]
    public function supports(RevisionInterface $revision): bool
    {
        return $revision instanceof CompanyRevision;
    }

    #[Override]
    protected function spawnDraft(RevisionInterface $source): CompanyRevision
    {
        assert($source instanceof CompanyRevision);

        $company = $source->getCompany();

        $draft = new CompanyRevision();
        $draft->setPreviousRevision($source);
        $company->addRevision($draft);
        $company->setCurrentRevision($draft);

        return $draft;
    }

    #[Override]
    protected function copyContent(
        RevisionInterface $source,
        AbstractRevision $draft,
    ): void {
        assert($source instanceof CompanyRevision);
        assert($draft instanceof CompanyRevision);

        $draft->setSlogan($source->getSlogan()->copy());
        $draft->setDescription($source->getDescription()->copy());
        $draft->setWebsite($source->getWebsite()->copy());
        $draft->setLogo($source->getLogo());
        $draft->setContactName($source->getContactName());
        $draft->setContactAddress($source->getContactAddress());
        $draft->setContactEmail($source->getContactEmail());
        $draft->setContactPhone($source->getContactPhone());
    }
}
