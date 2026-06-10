<?php

declare(strict_types=1);

namespace App\Service\Career;

use App\Entity\Application\RevisionInterface;
use App\Entity\Career\CompanyRevision;
use App\Workflow\RevisionClonerInterface;
use Override;

use function assert;

/**
 * Spawns the next Draft {@see CompanyRevision} from an existing one (for "changes requested", reopening, or editing
 * an approved company profile). The localised texts are deep-copied into fresh rows so orphan-removal can never delete
 * the source revision's content; the logo and contact details are copied by value. Authorship (member or company
 * user) carries forward.
 */
final readonly class CompanyRevisionCloner implements RevisionClonerInterface
{
    #[Override]
    public function supports(RevisionInterface $revision): bool
    {
        return $revision instanceof CompanyRevision;
    }

    #[Override]
    public function cloneAsDraft(RevisionInterface $source): CompanyRevision
    {
        assert($source instanceof CompanyRevision);

        $company = $source->getCompany();

        $draft = new CompanyRevision();
        $draft->setAuthor($source->getAuthor());
        $draft->setAuthorCompanyUser($source->getAuthorCompanyUser());
        $draft->setRevisionNumber($source->getRevisionNumber() + 1);
        $draft->setPreviousRevision($source);
        $draft->setSlogan($source->getSlogan()->copy());
        $draft->setDescription($source->getDescription()->copy());
        $draft->setWebsite($source->getWebsite()->copy());
        $draft->setLogo($source->getLogo());
        $draft->setContactName($source->getContactName());
        $draft->setContactAddress($source->getContactAddress());
        $draft->setContactEmail($source->getContactEmail());
        $draft->setContactPhone($source->getContactPhone());

        $company->addRevision($draft);
        $company->setCurrentRevision($draft);

        return $draft;
    }
}
