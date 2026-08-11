<?php

declare(strict_types=1);

namespace App\Twig\Components\Career;

use App\Entity\Career\Company;
use App\Entity\Career\Enums\VacancyCategories;
use App\Entity\Career\Vacancy;
use App\Entity\Career\VacancyLabel;
use App\Repository\Career\CompanyRepository;
use App\Repository\Career\VacancyLabelRepository;
use App\Repository\Career\VacancyRepository;
use Random\Engine\Mt19937;
use Random\Randomizer;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Metadata\UrlMapping;

use function array_filter;
use function array_intersect;
use function array_map;
use function array_slice;
use function array_values;
use function count;
use function explode;
use function is_array;
use function mt_getrandmax;
use function random_int;
use function strval;

/**
 * Backs the public vacancies overview: the whole filter set (category, owning company, labels and a free-text search)
 * lives here and mirrors itself into the query string, so the address bar is a shareable, reload-safe link. A company
 * card's per-category link lands here with `?category=...&company=...` pre-applied. Infinite scroll grows `limit`
 * through the loadMore action.
 *
 * As on the company overview, the vacancies are listed in a random order, so that a company is not structurally
 * favoured by where its name falls in the alphabet. The order is drawn once, at mount, and carried along as a seed for
 * as long as the visitor stays on the page; without that, every filter keystroke and every loaded page would reshuffle
 * the list under the reader. Reloading the page deals a new hand.
 */
#[AsLiveComponent(
    name: 'Career:VacancyOverview',
    template: 'components/Career/VacancyOverview.html.twig',
)]
final class VacancyOverview
{
    use DefaultActionTrait;

    public const int PAGE_SIZE = 16;

    #[LiveProp(
        writable: true,
        url: true,
    )]
    public ?string $category = null;

    #[LiveProp(
        writable: true,
        url: new UrlMapping(as: 'company'),
    )]
    public ?string $companyFilter = null;

    #[LiveProp(
        writable: true,
        url: true,
    )]
    public string $search = '';

    /** @var int[] */
    #[LiveProp(
        writable: true,
        url: new UrlMapping(as: 'labels'),
    )]
    public array $labelFilters = [];

    // Neither is client-writable: they travel in the signed props, so a crafted request can neither reshuffle the list
    // mid-page nor ask for an arbitrarily large page. The seed's ceiling keeps it inside the range JavaScript
    // represents exactly, since the props go through JSON.parse in the browser and a rounded seed fails the checksum.
    #[LiveProp]
    public int $seed = 0;

    #[LiveProp]
    public int $limit = self::PAGE_SIZE;

    /** @var int[]|null */
    private ?array $ids = null;

    /** @var int[]|null */
    private ?array $highlights = null;

    /** @var Vacancy[]|null */
    private ?array $vacancies = null;

    /** @var Vacancy[]|null */
    private ?array $highlighted = null;

    /** @var VacancyLabel[]|null */
    private ?array $labels = null;

    public function __construct(
        private readonly VacancyRepository $vacancyRepository,
        private readonly VacancyLabelRepository $vacancyLabelRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * Draw this visitor's order, and pre-select the label filter from the query string on a full page load.
     * ux-live-component v3 hydrates scalar url-mapped props server-side but leaves an array prop
     * ({@see $labelFilters}) empty, and the filter panel is `data-live-ignore`, so the client-side sync never re-checks
     * the boxes. Reading the ids here makes a shared or reloaded `?labels=` URL pre-select them. Runs only on the
     * initial render, not on live re-renders.
     */
    public function mount(): void
    {
        $this->seed = random_int(
            0,
            mt_getrandmax(),
        );

        $request = $this->requestStack->getCurrentRequest();
        if (
            null === $request
            || !$request->query->has('labels')
        ) {
            return;
        }

        $raw = $request->query->all()['labels'];
        $values = is_array($raw)
            ? $raw
            : explode(
                ',',
                strval($raw),
            );

        $this->labelFilters = self::positiveIntIds($values);
    }

    #[LiveAction]
    public function loadMore(): void
    {
        $this->limit += self::PAGE_SIZE;
    }

    /**
     * @return Vacancy[]
     */
    public function getVacancies(): array
    {
        return $this->vacancies ??= $this->vacancyRepository->findForOverviewByIds(
            array_slice(
                $this->shuffledIds(),
                0,
                $this->limit,
            ),
        );
    }

    /**
     * The highlighted vacancies among the current results, for the strip above the grid. Narrowed by the filters, so
     * filtering down to internships does not leave a highlighted full-time job sitting on top.
     *
     * @return Vacancy[]
     */
    public function getHighlightedVacancies(): array
    {
        return $this->highlighted ??= $this->vacancyRepository->findForOverviewByIds(
            array_values(
                array_intersect(
                    $this->shuffledIds(),
                    $this->highlightedIds(),
                ),
            ),
        );
    }

    /**
     * @return int[]
     */
    public function getHighlightedIds(): array
    {
        return $this->highlightedIds();
    }

    public function getTotalCount(): int
    {
        return count($this->matchingIds());
    }

    public function hasMore(): bool
    {
        return $this->getTotalCount() > count($this->getVacancies());
    }

    /**
     * @return VacancyCategories[]
     */
    public function getCategories(): array
    {
        return VacancyCategories::cases();
    }

    /**
     * @return Company[]
     */
    public function getCompanies(): array
    {
        return $this->companyRepository->findAllPublic();
    }

    /**
     * The filter panel reads this twice (once to decide whether to draw the block, once for the checkboxes), so it is
     * fetched once per render, with the localised names the checkboxes are labelled with.
     *
     * @return VacancyLabel[]
     */
    public function getLabels(): array
    {
        return $this->labels ??= $this->vacancyLabelRepository->findAllWithName();
    }

    /**
     * The matching vacancies in this visitor's order. A seeded Mt19937 rather than the global generator, so drawing
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
    private function highlightedIds(): array
    {
        return $this->highlights ??= $this->vacancyRepository->findHighlightedIds();
    }

    /**
     * @return int[]
     */
    private function matchingIds(): array
    {
        return $this->ids ??= $this->vacancyRepository->findActiveIdsForOverview(
            category: null !== $this->category
                ? VacancyCategories::tryFrom($this->category)
                : null,
            companySlugName: '' !== (string) $this->companyFilter
                ? $this->companyFilter
                : null,
            labelIds: self::positiveIntIds($this->labelFilters),
            search: $this->search,
        );
    }

    /**
     * Normalise a raw list of label-id values into a clean, re-indexed list of positive ints (dropping blanks, zero and
     * negatives). Shared by mount() (query-string parsing) and matchingIds() (filtering) so the two can never drift.
     *
     * @param array<mixed> $values
     *
     * @return int[]
     */
    private static function positiveIntIds(array $values): array
    {
        return array_values(
            array_filter(
                array_map(
                    'intval',
                    $values,
                ),
                static fn (int $id): bool => $id > 0,
            ),
        );
    }
}
