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
     * The Assignments this Label belongs to.
     *
     * @var Collection<array-key, Vacancy>
     */
    #[ManyToMany(
        targetEntity: Vacancy::class,
        mappedBy: 'labels',
        cascade: ['persist'],
    )]
    private Collection $vacancies;

    public function __construct()
    {
        $this->vacancies = new ArrayCollection();
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
     * Gets the vacancies associated with this label.
     *
     * @return Collection<array-key, Vacancy>
     */
    public function getVacancies(): Collection
    {
        return $this->vacancies;
    }

    public function addVacancy(Vacancy $vacancy): void
    {
        if ($this->vacancies->contains($vacancy)) {
            return;
        }

        $this->vacancies->add($vacancy);
    }

    public function removeVacancy(Vacancy $vacancy): void
    {
        if (!$this->vacancies->contains($vacancy)) {
            return;
        }

        $this->vacancies->removeElement($vacancy);
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
