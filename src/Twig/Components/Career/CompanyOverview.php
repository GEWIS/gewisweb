<?php

declare(strict_types=1);

namespace App\Twig\Components\Career;

use App\Entity\Career\Company;
use App\Repository\Career\CompanyRepository;
use Random\Engine\Mt19937;
use Random\Randomizer;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use function array_slice;
use function count;
use function mt_getrandmax;
use function random_int;

/**
 * Backs the public company overview: a free-text search that mirrors itself into the query string, and infinite scroll
 * that grows `limit` through the loadMore action.
 *
 * The companies are listed in a random order, so that no company is structurally favoured by being early in the
 * alphabet. Paging through a list that is reshuffled on every request would show some companies twice and others not
 * at all, so the order is drawn once, at mount, and carried along as a seed for as long as the visitor stays on the
 * page. Reloading it deals a new hand.
 */
#[AsLiveComponent(
    name: 'Career:CompanyOverview',
    template: 'components/Career/CompanyOverview.html.twig',
)]
final class CompanyOverview
{
    use DefaultActionTrait;

    public const int PAGE_SIZE = 12;

    #[LiveProp(
        writable: true,
        url: true,
    )]
    public string $search = '';

    // Neither is client-writable: they travel in the signed props, so a crafted request can neither reshuffle the list
    // mid-page nor ask for an arbitrarily large page. The seed's ceiling keeps it inside the range JavaScript
    // represents exactly, since the props go through JSON.parse in the browser and a rounded seed fails the checksum.
    #[LiveProp]
    public int $seed = 0;

    #[LiveProp]
    public int $limit = self::PAGE_SIZE;

    /** @var int[]|null */
    private ?array $ids = null;

    /** @var Company[]|null */
    private ?array $companies = null;

    public function __construct(private readonly CompanyRepository $companyRepository)
    {
    }

    public function mount(): void
    {
        $this->seed = random_int(
            0,
            mt_getrandmax(),
        );
    }

    #[LiveAction]
    public function loadMore(): void
    {
        $this->limit += self::PAGE_SIZE;
    }

    /**
     * @return Company[]
     */
    public function getCompanies(): array
    {
        return $this->companies ??= $this->companyRepository->findPublicByIds(
            array_slice(
                $this->shuffledIds(),
                0,
                $this->limit,
            ),
        );
    }

    public function getTotalCount(): int
    {
        return count($this->matchingIds());
    }

    public function hasMore(): bool
    {
        return $this->getTotalCount() > count($this->getCompanies());
    }

    /**
     * The matching companies in this visitor's order. A seeded Mt19937 rather than the global generator, so drawing
     * this hand leaves the rest of the request's randomness alone.
     *
     * @return int[]
     */
    private function shuffledIds(): array
    {
        return new Randomizer(new Mt19937($this->seed))->shuffleArray($this->matchingIds());
    }

    /**
     * @return int[]
     */
    private function matchingIds(): array
    {
        return $this->ids ??= $this->companyRepository->findPublicIds($this->search);
    }
}
