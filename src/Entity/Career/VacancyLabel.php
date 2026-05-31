<?php

declare(strict_types=1);

namespace App\Entity\Career;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Repository\Career\VacancyLabelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Vacancy Label model.
 *
 * @psalm-type VacancyLabelArrayType = array{
 *     id: int,
 *     name: ?string,
 *     nameEn: ?string,
 *     abbreviation: ?string,
 *     abbreviationEn: ?string,
 * }
 */
#[Entity(repositoryClass: VacancyLabelRepository::class)]
class VacancyLabel
{
    use IdentifiableTrait;

    /**
     * The name of the label.
     */
    #[OneToOne(
        targetEntity: CareerLocalisedText::class,
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
    private CareerLocalisedText $name;

    /**
     * The abbreviation of the label.
     */
    #[OneToOne(
        targetEntity: CareerLocalisedText::class,
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'abbreviation_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private CareerLocalisedText $abbreviation;

    /**
     * The vacancy revisions this Label is assigned to (labels live on the revision so their changes are reviewable).
     *
     * @var Collection<array-key, VacancyRevision>
     */
    #[ManyToMany(
        targetEntity: VacancyRevision::class,
        mappedBy: 'labels',
        cascade: ['persist'],
    )]
    private Collection $revisions;

    public function __construct()
    {
        $this->revisions = new ArrayCollection();
    }

    /**
     * Gets the name.
     */
    public function getName(): CareerLocalisedText
    {
        return $this->name;
    }

    /**
     * Sets the name.
     */
    public function setName(CareerLocalisedText $name): void
    {
        $this->name = $name;
    }

    /**
     * Gets the slug.
     */
    public function getAbbreviation(): CareerLocalisedText
    {
        return $this->abbreviation;
    }

    /**
     * Sets the slug.
     */
    public function setAbbreviation(CareerLocalisedText $slug): void
    {
        $this->abbreviation = $slug;
    }

    /**
     * Gets the vacancy revisions associated with this label.
     *
     * @return Collection<array-key, VacancyRevision>
     */
    public function getRevisions(): Collection
    {
        return $this->revisions;
    }

    public function addRevision(VacancyRevision $revision): void
    {
        if ($this->revisions->contains($revision)) {
            return;
        }

        $this->revisions->add($revision);
    }

    public function removeRevision(VacancyRevision $revision): void
    {
        if (!$this->revisions->contains($revision)) {
            return;
        }

        $this->revisions->removeElement($revision);
    }

    /**
     * @return VacancyLabelArrayType
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName()->getValueNL(),
            'nameEn' => $this->getName()->getValueEN(),
            'abbreviation' => $this->getAbbreviation()->getValueNL(),
            'abbreviationEn' => $this->getAbbreviation()->getValueEN(),
        ];
    }
}
