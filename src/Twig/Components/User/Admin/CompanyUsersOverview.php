<?php

declare(strict_types=1);

namespace App\Twig\Components\User\Admin;

use App\Entity\User\CompanyUser;
use App\Entity\User\Enums\UserRoles;
use App\Repository\User\CompanyUserRepository;
use App\View\User\Admin\CompanyUserRow;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use function array_map;
use function ceil;
use function in_array;
use function iterator_to_array;
use function max;
use function min;

#[AsLiveComponent(
    name: 'User:Admin:CompanyUsersOverview',
    template: 'components/User/Admin/CompanyUsersOverview.html.twig',
)]
#[IsGranted(UserRoles::Admin->value)]
#[IsGranted('SUDO')]
final class CompanyUsersOverview
{
    use DefaultActionTrait;

    public const int PAGE_SIZE = 25;

    private const array ALLOWED_SORTS = [
        'company',
        'name',
        'email',
        'mfa',
    ];

    #[LiveProp(writable: true)]
    public string $search = '';

    #[LiveProp(writable: true)]
    public string $sort = 'company';

    #[LiveProp(writable: true)]
    public string $direction = 'asc';

    #[LiveProp(writable: true)]
    public int $page = 1;

    /** @var Paginator<CompanyUser>|null */
    private ?Paginator $paginator = null;

    public function __construct(private readonly CompanyUserRepository $companyUserRepository)
    {
    }

    /**
     * @return list<CompanyUserRow>
     */
    public function getRows(): array
    {
        return array_map(
            static fn (CompanyUser $cu): CompanyUserRow => CompanyUserRow::fromCompanyUser($cu),
            iterator_to_array(
                $this->getPaginator()->getIterator(),
                false,
            ),
        );
    }

    public function getTotalCount(): int
    {
        return $this->getPaginator()->count();
    }

    /**
     * @return Paginator<CompanyUser>
     */
    private function getPaginator(): Paginator
    {
        return $this->paginator ??= $this->companyUserRepository->paginateForAdmin(
            search: $this->search,
            sort: $this->effectiveSort(),
            direction: $this->direction,
            page: max(
                1,
                $this->page,
            ),
            pageSize: self::PAGE_SIZE,
        );
    }

    public function getTotalPages(): int
    {
        return max(
            1,
            (int) ceil($this->getTotalCount() / self::PAGE_SIZE),
        );
    }

    #[LiveAction]
    public function toggleSort(#[LiveArg]
    string $column,): void
    {
        if (
            !in_array(
                $column,
                self::ALLOWED_SORTS,
                true,
            )
        ) {
            return;
        }

        if ($this->sort === $column) {
            $this->direction = 'asc' === $this->direction
                ? 'desc'
                : 'asc';
        } else {
            $this->sort = $column;
            $this->direction = 'asc';
        }

        $this->page = 1;
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

    private function effectiveSort(): string
    {
        return in_array(
            $this->sort,
            self::ALLOWED_SORTS,
            true,
        )
            ? $this->sort
            : 'company';
    }
}
