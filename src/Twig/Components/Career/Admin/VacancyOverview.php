<?php

declare(strict_types=1);

namespace App\Twig\Components\Career\Admin;

use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Career\Company;
use App\Entity\Career\Enums\VacancyCategories;
use App\Entity\Career\Vacancy;
use App\Entity\User\Enums\UserRoles;
use App\Repository\Career\CompanyRepository;
use App\Repository\Career\VacancyRepository;
use App\Twig\Components\Concerns\PageSizeTrait;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Metadata\UrlMapping;

use function ceil;
use function intval;
use function iterator_to_array;
use function max;
use function min;
use function strval;

/**
 * The vacancies tab of the career overview. Every filter is a live prop, so the list narrows as the boxes are used and
 * the address bar stays a shareable link.
 *
 * Rendered twice: on its own tab, where the company can be picked, and on a single company's page, where {@see
 * $company} is passed in and pins the list to that company.
 */
#[AsLiveComponent(
    name: 'Career:Admin:VacancyOverview',
    template: 'components/Career/Admin/VacancyOverview.html.twig',
)]
#[IsGranted(UserRoles::CompanyAdmin->value)]
final class VacancyOverview
{
    use DefaultActionTrait;
    use PageSizeTrait;

    #[LiveProp]
    public ?Company $company = null;

    #[LiveProp(
        writable: true,
        url: true,
        onUpdated: 'onFilterUpdated',
    )]
    public string $search = '';

    #[LiveProp(
        writable: true,
        url: true,
        onUpdated: 'onFilterUpdated',
    )]
    public ?string $status = null;

    #[LiveProp(
        writable: true,
        url: true,
        onUpdated: 'onFilterUpdated',
    )]
    public ?string $category = null;

    /**
     * The picked company's id, kept as a string because the empty option of a `<select>` posts an empty value rather
     * than a number.
     */
    #[LiveProp(
        writable: true,
        url: new UrlMapping(as: 'company'),
        onUpdated: 'onFilterUpdated',
    )]
    public ?string $companyFilter = null;

    #[LiveProp(writable: true)]
    public int $page = 1;

    /** @var Paginator<Vacancy>|null */
    private ?Paginator $paginator = null;

    public function __construct(
        private readonly VacancyRepository $vacancyRepository,
        private readonly CompanyRepository $companyRepository,
    ) {
    }

    public function onFilterUpdated(): void
    {
        $this->page = 1;
    }

    public function isPinnedToACompany(): bool
    {
        return null !== $this->company;
    }

    /**
     * @return list<Vacancy>
     */
    public function getVacancies(): array
    {
        return iterator_to_array(
            $this->getPaginator()->getIterator(),
            false,
        );
    }

    public function getTotalCount(): int
    {
        return $this->getPaginator()->count();
    }

    public function getTotalPages(): int
    {
        return max(
            1,
            (int) ceil($this->getTotalCount() / $this->pageSize()),
        );
    }

    /**
     * @return RevisionStatus[]
     */
    public function getStatuses(): array
    {
        return RevisionStatus::cases();
    }

    /**
     * @return VacancyCategories[]
     */
    public function getCategories(): array
    {
        return VacancyCategories::cases();
    }

    /**
     * @return list<Company>
     */
    public function getCompanies(): array
    {
        return $this->companyRepository->findForAdminOverview();
    }

    #[LiveAction]
    public function gotoPage(#[LiveArg]
    int $page,): void
    {
        $this->page = max(
            1,
            min(
                $page,
                $this->getTotalPages(),
            ),
        );
    }

    private function companyId(): ?int
    {
        if (null !== $this->company) {
            return $this->company->getId();
        }

        return '' !== strval($this->companyFilter)
            ? intval($this->companyFilter)
            : null;
    }

    /**
     * @return Paginator<Vacancy>
     */
    private function getPaginator(): Paginator
    {
        return $this->paginator ??= $this->vacancyRepository->paginateForAdmin(
            search: $this->search,
            status: null !== $this->status
                ? RevisionStatus::tryFrom($this->status)
                : null,
            category: null !== $this->category
                ? VacancyCategories::tryFrom($this->category)
                : null,
            companyId: $this->companyId(),
            page: max(
                1,
                $this->page,
            ),
            pageSize: $this->pageSize(),
        );
    }
}
