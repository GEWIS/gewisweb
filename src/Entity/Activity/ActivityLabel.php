<?php

declare(strict_types=1);

namespace App\Entity\Activity;

use App\Entity\Application\LocalisedText as LocalisedTextModel;
use App\Entity\Application\Traits\IdentifiableTrait;
use App\Repository\Activity\ActivityLabelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Activity Label model.
 *
 * @psalm-type ActivityLabelArrayType = array{
 *     id: int,
 *     name: ?string,
 *     nameEn: ?string,
 * }
 * @psalm-import-type LocalisedTextGdprArrayType from LocalisedTextModel as ImportedLocalisedTextGdprArrayType
 * @psalm-type ActivityLabelGdprArrayType = array{
 *     id: int,
 *     name: ImportedLocalisedTextGdprArrayType,
 * }
 */
#[Entity(repositoryClass: ActivityLabelRepository::class)]
class ActivityLabel
{
    use IdentifiableTrait;

    /**
     * The activity revisions this Label is assigned to (labels live on the revision so their changes are reviewable).
     *
     * @var Collection<array-key, ActivityRevision>
     */
    #[ManyToMany(
        targetEntity: ActivityRevision::class,
        mappedBy: 'labels',
        cascade: ['persist'],
    )]
    private Collection $revisions;

    /**
     * Name for the Label.
     */
    #[OneToOne(
        targetEntity: ActivityLocalisedText::class,
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'name_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private ActivityLocalisedText $name;

    public function __construct()
    {
        $this->revisions = new ArrayCollection();
    }

    public function addRevision(ActivityRevision $revision): void
    {
        if ($this->revisions->contains($revision)) {
            return;
        }

        $this->revisions->add($revision);
    }

    public function removeRevision(ActivityRevision $revision): void
    {
        if (!$this->revisions->contains($revision)) {
            return;
        }

        $this->revisions->removeElement($revision);
    }

    public function getName(): ActivityLocalisedText
    {
        return $this->name;
    }

    public function setName(ActivityLocalisedText $name): void
    {
        $this->name = $name;
    }

    /**
     * @return ActivityRevision[]
     */
    public function getRevisions(): array
    {
        return $this->revisions->toArray();
    }

    /**
     * @return ActivityLabelArrayType
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName()->getValueNL(),
            'nameEn' => $this->getName()->getValueEN(),
        ];
    }

    /**
     * @return ActivityLabelGdprArrayType
     */
    public function toGdprArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName()->toGdprArray(),
        ];
    }
}
