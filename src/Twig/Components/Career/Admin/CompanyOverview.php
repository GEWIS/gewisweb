<?php

declare(strict_types=1);

namespace App\Twig\Components\Career\Admin;

use App\Entity\Career\Company;
use App\Entity\User\Enums\UserRoles;
use App\Repository\Career\CompanyRepository;
use App\Twig\Components\Concerns\PageSizeTrait;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use function ceil;
use function iterator_to_array;
use function max;
use function min;

/**
 * The companies tab of the career overview: every company on the books, hidden and out of contract ones included,
 * searchable by name or slug and paged through.
 */
#[AsLiveComponent(
    name: 'Career:Admin:CompanyOverview',
    template: 'components/Career/Admin/CompanyOverview.html.twig',
)]
#[IsGranted(UserRoles::CompanyAdmin->value)]
final class CompanyOverview
{
    use DefaultActionTrait;
    use PageSizeTrait;

    #[LiveProp(
        writable: true,
        url: true,
        onUpdated: 'onSearchUpdated',
    )]
    public string $search = '';

    #[LiveProp(writable: true)]
    public int $page = 1;

    /** @var Paginator<Company>|null */
    private ?Paginator $paginator = null;

    public function __construct(private readonly CompanyRepository $companyRepository)
    {
    }

    public function onSearchUpdated(): void
    {
        $this->page = 1;
    }

    /**
     * @return list<Company>
     */
    public function getCompanies(): array
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

    /**
     * @return Paginator<Company>
     */
    private function getPaginator(): Paginator
    {
        return $this->paginator ??= $this->companyRepository->paginateForAdmin(
            search: $this->search,
            page: max(
                1,
                $this->page,
            ),
            pageSize: $this->pageSize(),
        );
    }
}
