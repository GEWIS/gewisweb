<?php

declare(strict_types=1);

namespace App\Entity\Decision;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Application\Traits\TimestampableTrait;
use App\Repository\Decision\ReferenceDocumentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OrderBy;

/**
 * A document from the association-wide reference library, e.g. "Scenarios and Procedures". Each meeting selects which
 * library documents apply to it through {@see MeetingReferenceSelection}, following the latest version by default or
 * pinned to a specific one.
 */
#[Entity(repositoryClass: ReferenceDocumentRepository::class)]
#[HasLifecycleCallbacks]
class ReferenceDocument
{
    use IdentifiableTrait;
    use TimestampableTrait;

    /**
     * Name of the document.
     */
    #[Column(type: Types::STRING)]
    private string $name;

    /**
     * The versions of this document, in upload order.
     *
     * @var Collection<array-key, ReferenceDocumentVersion>
     */
    #[OneToMany(
        targetEntity: ReferenceDocumentVersion::class,
        mappedBy: 'referenceDocument',
    )]
    #[OrderBy(value: ['id' => 'ASC'])]
    private Collection $versions;

    public function __construct()
    {
        $this->versions = new ArrayCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return Collection<array-key, ReferenceDocumentVersion>
     */
    public function getVersions(): Collection
    {
        return $this->versions;
    }

    public function addVersion(ReferenceDocumentVersion $version): void
    {
        $this->versions[] = $version;
    }

    public function getLatestVersion(): ?ReferenceDocumentVersion
    {
        $latest = $this->versions->last();

        return false === $latest
            ? null
            : $latest;
    }
}
