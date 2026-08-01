<?php

declare(strict_types=1);

namespace App\Twig\Components\Decision;

use App\Entity\Decision\Enums\MeetingTypes;
use App\Entity\Decision\Meeting;
use App\Entity\User\Enums\UserRoles;
use App\Repository\Decision\MeetingRepository;
use App\ViewModel\Decision\MeetingOverviewRow;
use App\ViewModel\Decision\MeetingStatus;
use InvalidArgumentException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use function array_map;
use function ceil;
use function in_array;
use function max;
use function min;
use function preg_match;
use function strtoupper;
use function trim;

#[AsLiveComponent(
    name: 'Decision:MeetingOverview',
    template: 'components/Decision/MeetingOverview.html.twig',
)]
#[IsGranted(UserRoles::User->value)]
final class MeetingOverview
{
    use DefaultActionTrait;

    public const int PAGE_SIZE = 25;

    private const array TYPE_TOKENS = [
        'gmm',
        'bm',
        'cm',
    ];

    /**
     * The member-facing type token (gmm/bm/cm); null shows every regular type. Virtual meetings exist to repair the
     * record and are reachable by direct link or an explicit "VIRT 123" search, never through the tabs.
     */
    #[LiveProp(
        writable: true,
        url: true,
        onUpdated: 'resetPage',
    )]
    public ?string $type = null;

    /**
     * A meeting reference to narrow the list to: a number ("214") or a type with a number ("GMM 214").
     */
    #[LiveProp(
        writable: true,
        url: true,
        onUpdated: 'resetPage',
    )]
    public string $search = '';

    #[LiveProp(writable: true)]
    public int $page = 1;

    /** @var array{items: list<array{0: Meeting, 1: int, 2: int}>, total: int}|null */
    private ?array $result = null;

    public function __construct(private readonly MeetingRepository $meetingRepository)
    {
    }

    /**
     * @return list<MeetingOverviewRow>
     */
    public function getRows(): array
    {
        return array_map(
            static fn (array $item): MeetingOverviewRow => new MeetingOverviewRow(
                $item[0],
                $item[1],
                $item[2] > 0,
                MeetingStatus::derive(
                    $item[0]->getDate(),
                    $item[1] > 0,
                    $item[2] > 0,
                ),
            ),
            $this->getResult()['items'],
        );
    }

    public function getTotalCount(): int
    {
        return $this->getResult()['total'];
    }

    public function getTotalPages(): int
    {
        return max(
            1,
            (int) ceil($this->getTotalCount() / self::PAGE_SIZE),
        );
    }

    /**
     * The type filter tabs: the member-facing token with the enum case behind it.
     *
     * @return list<array{token: string, type: MeetingTypes}>
     */
    public function getTypeOptions(): array
    {
        return array_map(
            static fn (string $token): array => [
                'token' => $token,
                'type' => MeetingTypes::tryFromSearch(strtoupper($token)),
            ],
            self::TYPE_TOKENS,
        );
    }

    public function resetPage(): void
    {
        $this->page = 1;
    }

    #[LiveAction]
    public function filterType(#[LiveArg]
    ?string $type,): void
    {
        $this->type = in_array(
            $type,
            self::TYPE_TOKENS,
            true,
        )
            ? $type
            : null;
        $this->resetPage();
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
     * @return array{items: list<array{0: Meeting, 1: int, 2: int}>, total: int}
     */
    private function getResult(): array
    {
        return $this->result ??= $this->meetingRepository->paginateForOverview(
            $this->resolveType(),
            $this->resolveNumber(),
            max(
                1,
                $this->page,
            ),
            self::PAGE_SIZE,
            excludeVirtual: true,
        );
    }

    private function resolveType(): ?MeetingTypes
    {
        $search = $this->parseSearch();
        if (null !== $search['type']) {
            return $search['type'];
        }

        if (
            null === $this->type
            || !in_array(
                $this->type,
                self::TYPE_TOKENS,
                true,
            )
        ) {
            return null;
        }

        return MeetingTypes::tryFromSearch(strtoupper($this->type));
    }

    private function resolveNumber(): ?int
    {
        return $this->parseSearch()['number'];
    }

    /**
     * @return array{type: ?MeetingTypes, number: ?int}
     */
    private function parseSearch(): array
    {
        $search = trim($this->search);
        $parsed = [
            'type' => null,
            'number' => null,
        ];

        if (
            1 === preg_match(
                '/^([a-z]+)?\s*(\d+)?$/i',
                $search,
                $matches,
            )
        ) {
            if ('' !== ($matches[1] ?? '')) {
                try {
                    $parsed['type'] = MeetingTypes::tryFromSearch(strtoupper($matches[1]));
                } catch (InvalidArgumentException) {
                    // An unknown type keyword simply does not narrow the list.
                }
            }

            if ('' !== ($matches[2] ?? '')) {
                $parsed['number'] = (int) $matches[2];
            }
        }

        return $parsed;
    }
}
