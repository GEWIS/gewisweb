<?php

declare(strict_types=1);

namespace App\Entity\Career;

use App\Entity\Application\AbstractSocialLink;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * Somewhere a company can be followed, as said by one revision of its profile.
 */
#[Entity]
class CompanySocialLink extends AbstractSocialLink
{
    #[ManyToOne(
        targetEntity: CompanyRevision::class,
        inversedBy: 'socialLinks',
    )]
    #[JoinColumn(nullable: false)]
    private CompanyRevision $revision;

    public function getRevision(): CompanyRevision
    {
        return $this->revision;
    }

    public function setRevision(CompanyRevision $revision): void
    {
        $this->revision = $revision;
    }
}
