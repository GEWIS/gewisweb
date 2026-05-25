<?php

declare(strict_types=1);

namespace App\Twig\Components\User\Admin;

use App\Entity\Decision\Enums\MembershipTypes;
use App\Entity\Decision\Member;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;
use App\Repository\Decision\MemberRepository;
use App\Repository\User\UserRepository;
use App\View\User\Admin\MemberRow;
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
    name: 'User:Admin:UsersOverview',
    template: 'components/User/Admin/UsersOverview.html.twig',
)]
#[IsGranted(UserRoles::Admin->value)]
#[IsGranted('SUDO')]
final class UsersOverview
{
    use DefaultActionTrait;

    public const int PAGE_SIZE = 25;

    private const array ALLOWED_SORTS = [
        'lidnr',
        'name',
        'type',
        'expiration',
    ];

    #[LiveProp(writable: true)]
    public string $search = '';

    #[LiveProp(writable: true)]
    public string $sort = 'lidnr';

    #[LiveProp(writable: true)]
    public string $direction = 'asc';

    #[LiveProp(writable: true)]
    public int $page = 1;

    #[LiveProp(writable: true)]
    public ?string $typeFilter = null;

    #[LiveProp(writable: true)]
    public bool $hiddenOnly = false;

    #[LiveProp(writable: true)]
    public bool $deletedOnly = false;

    #[LiveProp(writable: true)]
    public bool $expiredOnly = false;

    #[LiveProp(writable: true)]
    public bool $activatedOnly = false;

    #[LiveProp(writable: true)]
    public bool $mfaOnly = false;

    /** @var Paginator<Member>|null */
    private ?Paginator $paginator = null;

    public function __construct(
        private readonly MemberRepository $memberRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    /**
     * @return list<MemberRow>
     */
    public function getRows(): array
    {
        $members = iterator_to_array(
            $this->getPaginator()->getIterator(),
            false,
        );

        // Hydrate the matching `User` per row in one extra query. Doctrine's LEFT JOIN above does not produce a
        // straightforward `Member -> ?User` mapping for unmanaged entities, so we look the users up explicitly.
        $lidnrs = array_map(
            static fn (Member $m): int => $m->getLidnr(),
            $members,
        );

        $users = $this->userRepository->findByLidnrsWithRoles($lidnrs);
        /** @var array<int, User> $usersByLidnr */
        $usersByLidnr = [];
        foreach ($users as $user) {
            $usersByLidnr[$user->getLidnr()] = $user;
        }

        return array_map(
            static fn (Member $m): MemberRow => MemberRow::fromMember(
                $m,
                $usersByLidnr[$m->getLidnr()] ?? null,
            ),
            $members,
        );
    }

    public function getTotalCount(): int
    {
        return $this->getPaginator()->count();
    }

    /**
     * @return Paginator<Member>
     */
    private function getPaginator(): Paginator
    {
        return $this->paginator ??= $this->memberRepository->paginateForAdmin(
            search: $this->search,
            sort: $this->effectiveSort(),
            direction: $this->direction,
            filters: [
                'type' => $this->resolveTypeFilter(),
                'hiddenOnly' => $this->hiddenOnly,
                'deletedOnly' => $this->deletedOnly,
                'expiredOnly' => $this->expiredOnly,
                'activatedOnly' => $this->activatedOnly,
                'mfaOnly' => $this->mfaOnly,
            ],
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

    /**
     * @return list<MembershipTypes>
     */
    public function getMembershipTypes(): array
    {
        return MembershipTypes::cases();
    }

    /**
     * @return list<string>
     */
    public function getAllowedSorts(): array
    {
        return self::ALLOWED_SORTS;
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
            : 'lidnr';
    }

    private function resolveTypeFilter(): ?MembershipTypes
    {
        if (
            null === $this->typeFilter
            || '' === $this->typeFilter
        ) {
            return null;
        }

        return MembershipTypes::tryFrom($this->typeFilter);
    }
}
