<?php

declare(strict_types=1);

namespace App\Twig\Components\Decision;

use App\Entity\Decision\Enums\OrganTypes;
use App\Entity\Decision\Organ;
use App\Repository\Decision\OrganRepository;
use Random\Engine\Mt19937;
use Random\Randomizer;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Metadata\UrlMapping;

use function array_slice;
use function count;
use function mt_getrandmax;
use function random_int;

/**
 * One overview of the bodies of a kind: the active ones, or the ones that have been abrogated. Every overview is the
 * same list with different arguments, so all of them are this component with the type and the scope passed in.
 *
 * A search narrows by abbreviation and name and mirrors itself into the query string, so a filtered list is a shareable
 * link. Infinite scroll grows `limit` through the loadMore action, as on the career overviews.
 *
 * The fraternities are shown in a random order, so no fraternity is structurally favoured by where its name falls in
 * the alphabet. The order is drawn once, at mount, and carried along as a seed for as long as the visitor stays on the
 * page; without that, every keystroke would reshuffle the list under the reader.
 */
#[AsLiveComponent(
    name: 'Decision:BodyOverview',
    template: 'components/Decision/BodyOverview.html.twig',
)]
final class BodyOverview
{
    use DefaultActionTrait;

    private const int PAGE_SIZE = 12;

    #[LiveProp]
    public string $type = '';

    #[LiveProp]
    public bool $abrogated = false;

    /**
     * Whether a link to a body carries the year it was founded. An abbreviation is reused, so the overviews that list
     * what is gone say which one they mean; the ones listing what is around never have to.
     */
    #[LiveProp]
    public bool $dated = false;

    #[LiveProp]
    public bool $shuffle = false;

    /** What the overview says when there is nothing in it at all, which differs per kind of body. */
    #[LiveProp]
    public string $emptyMessage = '';

    /**
     * Scoped per overview, so the two lists on a page of fraternities do not share one box in the address bar.
     */
    #[LiveProp(
        writable: true,
        url: true,
        modifier: 'scopeSearchToThisList',
    )]
    public string $search = '';

    // Neither is client-writable: they travel in the signed props, so a crafted request can neither reshuffle the list
    // mid-page nor ask for an arbitrarily large page.
    #[LiveProp]
    public int $seed = 0;

    #[LiveProp]
    public int $limit = self::PAGE_SIZE;

    /** @var int[]|null */
    private ?array $ids = null;

    /** @var Organ[]|null */
    private ?array $organs = null;

    public function __construct(private readonly OrganRepository $organRepository)
    {
    }

    public function mount(): void
    {
        $this->seed = random_int(
            0,
            mt_getrandmax(),
        );
    }

    /**
     * What the search of this overview is called in the address bar. A page shows what is installed above what has
     * been abrogated, and both are this component, so the two would otherwise write to and read from the same
     * parameter and filter each other.
     */
    public function scopeSearchToThisList(LiveProp $prop): LiveProp
    {
        if (!$this->abrogated) {
            return $prop;
        }

        return $prop->withUrl(new UrlMapping(as: 'abrogated-search'));
    }

    #[LiveAction]
    public function loadMore(): void
    {
        $this->limit += self::PAGE_SIZE;
    }

    /**
     * @return Organ[]
     */
    public function getOrgans(): array
    {
        return $this->organs ??= $this->organRepository->findOverviewByIds(array_slice(
            $this->orderedIds(),
            0,
            $this->limit,
        ));
    }

    public function getTotalCount(): int
    {
        return count($this->matchingIds());
    }

    public function hasMore(): bool
    {
        return $this->getTotalCount() > count($this->getOrgans());
    }

    /**
     * @return int[]
     */
    private function orderedIds(): array
    {
        if (!$this->shuffle) {
            return $this->matchingIds();
        }

        // A seeded Mt19937 rather than the global generator, so drawing this hand leaves the rest of the request's
        // randomness alone.
        return new Randomizer(new Mt19937($this->seed))->shuffleArray($this->matchingIds());
    }

    /**
     * @return int[]
     */
    private function matchingIds(): array
    {
        return $this->ids ??= $this->organRepository->findOverviewIds(
            OrganTypes::from($this->type),
            $this->abrogated,
            $this->search,
        );
    }
}
