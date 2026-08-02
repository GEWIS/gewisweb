<?php

declare(strict_types=1);

namespace App\Entity\Career;

use App\Entity\Career\Enums\CompanyPackageTypes;
use App\Repository\Career\CompanyHighlightPackageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Override;

/**
 * A package that puts a handful of a company's vacancies on the career landing page. Which ones is the company's own
 * choice, out of whatever it has running; there is no limit per category, since a company that has paid for the
 * package is trusted to know what it wants shown.
 *
 * The selection is checked when it is made and again when it is shown: a vacancy whose window closes, whose package
 * expires or which is taken down in the meantime drops out of the highlights on its own instead of leaving a dead
 * entry on the landing page.
 */
#[Entity(repositoryClass: CompanyHighlightPackageRepository::class)]
class CompanyHighlightPackage extends CompanyPackage
{
    /** @var Collection<array-key, Vacancy> */
    #[ManyToMany(targetEntity: Vacancy::class)]
    #[JoinTable(name: 'CompanyHighlightPackageVacancy')]
    private Collection $vacancies;

    public function __construct()
    {
        parent::__construct();

        $this->vacancies = new ArrayCollection();
    }

    /**
     * Everything the company picked, whether or not it is still showable.
     *
     * @return Collection<array-key, Vacancy>
     */
    public function getVacancies(): Collection
    {
        return $this->vacancies;
    }

    /**
     * What the landing page actually shows: the picks that are still live.
     *
     * @return list<Vacancy>
     */
    public function getDisplayableVacancies(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        $displayable = [];

        foreach ($this->vacancies as $vacancy) {
            if (!$vacancy->isActive()) {
                continue;
            }

            $displayable[] = $vacancy;
        }

        return $displayable;
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
        $this->vacancies->removeElement($vacancy);
    }

    /**
     * @param iterable<Vacancy> $vacancies
     */
    public function setVacancies(iterable $vacancies): void
    {
        $this->vacancies->clear();

        foreach ($vacancies as $vacancy) {
            $this->addVacancy($vacancy);
        }
    }

    #[Override]
    public function getType(): CompanyPackageTypes
    {
        return CompanyPackageTypes::Highlight;
    }
}
