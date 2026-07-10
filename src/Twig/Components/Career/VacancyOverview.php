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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Metadata\UrlMapping;

use function array_filter;
use function array_map;
use function array_values;
use function explode;
use function is_array;
use function strval;

/**
 * Backs the public vacancies overview: the whole filter set (category, owning company, labels and a free-text search)
 * lives here and mirrors itself into the query string, so the address bar is a shareable, reload-safe link. A company
 * card's per-category link lands here with `?category=...&company=...` pre-applied.
 */
#[AsLiveComponent(
    name: 'Career:VacancyOverview',
    template: 'components/Career/VacancyOverview.html.twig',
)]
final class VacancyOverview
{
    use DefaultActionTrait;

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

    /** @var Vacancy[]|null */
    private ?array $vacancies = null;

    public function __construct(
        private readonly VacancyRepository $vacancyRepository,
        private readonly VacancyLabelRepository $vacancyLabelRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * Pre-select the label filter from the query string on a full page load. ux-live-component v3 hydrates scalar
     * url-mapped props server-side but leaves an array prop ({@see $labelFilters}) empty, and the filter panel is
     * `data-live-ignore`, so the client-side sync never re-checks the boxes. Reading the ids here makes a shared or
     * reloaded `?labels=` URL pre-select them. Runs only on the initial render, not on live re-renders.
     */
    public function mount(): void
    {
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

    /**
     * @return Vacancy[]
     */
    public function getVacancies(): array
    {
        return $this->vacancies ??= $this->vacancyRepository->findForOverview(
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
     * @return VacancyLabel[]
     */
    public function getLabels(): array
    {
        return $this->vacancyLabelRepository->findBy(
            [],
            ['id' => 'ASC'],
        );
    }

    /**
     * Normalise a raw list of label-id values into a clean, re-indexed list of positive ints (dropping blanks, zero and
     * negatives). Shared by mount() (query-string parsing) and getVacancies() (filtering) so the two can never drift.
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
