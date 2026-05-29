<?php

declare(strict_types=1);

namespace App\Entity\Career;

use App\Entity\Career\Enums\CompanyPackageTypes;
use App\Entity\Career\VacancyCategory as VacancyCategoryModel;
use App\Repository\Career\CompanyJobPackageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OrderBy;
use Override;

use function array_filter;
use function count;

/**
 * CompanyPackage model.
 */
#[Entity(repositoryClass: CompanyJobPackageRepository::class)]
class CompanyJobPackage extends CompanyPackage
{
    /**
     * The package's vacancies.
     *
     * @var Collection<array-key, Vacancy>
     */
    #[OneToMany(
        targetEntity: Vacancy::class,
        mappedBy: 'package',
        cascade: [
            'persist',
            'remove',
        ],
    )]
    #[OrderBy(['updatedAt' => 'DESC'])]
    private Collection $vacancies;

    public function __construct()
    {
        parent::__construct();

        $this->vacancies = new ArrayCollection();
    }

    /**
     * Get the vacancies in the package.
     *
     * @return Collection<array-key, Vacancy>
     */
    public function getVacancies(): Collection
    {
        return $this->vacancies;
    }

    /**
     * Get the number of vacancies in the package.
     *
     * @return int of vacancies in the package
     */
    public function getNumberOfActiveJobs(?VacancyCategoryModel $category = null): int
    {
        return count($this->getJobsInCategory($category));
    }

    /**
     * Get the vacancies that are part of the given category.
     *
     * @return Vacancy[]
     */
    public function getJobsInCategory(?VacancyCategoryModel $category = null): array
    {
        $filter = static function (Vacancy $vacancy) use ($category): bool {
            if (null === $category) {
                return $vacancy->isActive();
            }

            return $vacancy->getCategory() === $category
                && $vacancy->isActive();
        };

        return array_filter(
            $this->vacancies->toArray(),
            $filter,
        );
    }

    /**
     * Adds a vacancy to the package.
     *
     * @param Vacancy $vacancy vacancy to be added
     */
    public function addVacancy(Vacancy $vacancy): void
    {
        $this->vacancies->add($vacancy);
    }

    /**
     * Removes a vacancy from the package.
     *
     * @param Vacancy $vacancy vacancy to be removed
     */
    public function removeVacancy(Vacancy $vacancy): void
    {
        $this->vacancies->removeElement($vacancy);
    }

    #[Override]
    public function getType(): CompanyPackageTypes
    {
        return CompanyPackageTypes::Job;
    }
}
