<?php

declare(strict_types=1);

namespace App\Entity\Decision;

use App\Entity\Application\AbstractSocialLink;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * Somewhere a body can be followed, as said by one revision of its page.
 */
#[Entity]
class OrganSocialLink extends AbstractSocialLink
{
    #[ManyToOne(
        targetEntity: OrganInformationRevision::class,
        inversedBy: 'socialLinks',
    )]
    #[JoinColumn(nullable: false)]
    private OrganInformationRevision $revision;

    public function getRevision(): OrganInformationRevision
    {
        return $this->revision;
    }

    public function setRevision(OrganInformationRevision $revision): void
    {
        $this->revision = $revision;
    }
}
