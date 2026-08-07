<?php

declare(strict_types=1);

namespace App\Twig\Components\Education;

use App\Entity\Education\Enums\CourseFilter;
use App\Entity\Education\Enums\CourseSort;
use App\Repository\Education\CourseRepository;
use App\ViewModel\Education\CourseOverviewRow;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use function array_filter;
use function array_map;
use function array_values;

/**
 * Search, filter and sort all mirror into the query string, so the address bar stays a shareable, reload-safe link and
 * the hero's stat tiles can link straight into a filtered view.
 *
 * Courses with nothing in them are listed like any other, because the archive is as much a record of what is missing as
 * of what is there. Those are the rows that get similar courses attached, so an empty course points somewhere.
 */
#[AsLiveComponent(
    name: 'Education:CourseOverview',
    template: 'components/Education/CourseOverview.html.twig',
)]
final class CourseOverview
{
    use DefaultActionTrait;

    #[LiveProp(
        writable: true,
        url: true,
    )]
    public string $search = '';

    #[LiveProp(
        writable: true,
        url: true,
    )]
    public string $filter = CourseFilter::All->value;

    #[LiveProp(
        writable: true,
        url: true,
    )]
    public string $sort = CourseSort::Code->value;

    /** @var CourseOverviewRow[]|null */
    private ?array $rows = null;

    public function __construct(private readonly CourseRepository $courseRepository)
    {
    }

    /**
     * @return CourseOverviewRow[]
     */
    public function getRows(): array
    {
        return $this->rows ??= $this->withSimilarCourses($this->courseRepository->findForOverview(
            $this->search,
            $this->currentFilter(),
            $this->currentSort(),
        ));
    }

    public function getTotal(): int
    {
        return $this->courseRepository->countAll();
    }

    public function isNarrowed(): bool
    {
        return '' !== $this->search
            || CourseFilter::All !== $this->currentFilter();
    }

    /**
     * @return CourseFilter[]
     */
    public function getFilters(): array
    {
        return CourseFilter::cases();
    }

    /**
     * @return CourseSort[]
     */
    public function getSorts(): array
    {
        return CourseSort::cases();
    }

    /**
     * Only the rows that have nothing of their own, in one query rather than one per row: a course with material
     * already has somewhere to send the reader.
     *
     * @param CourseOverviewRow[] $rows
     *
     * @return CourseOverviewRow[]
     */
    private function withSimilarCourses(array $rows): array
    {
        $emptyCodes = array_values(array_map(
            static fn (CourseOverviewRow $row): string => $row->code,
            array_filter(
                $rows,
                static fn (CourseOverviewRow $row): bool => $row->isEmpty(),
            ),
        ));

        if ([] === $emptyCodes) {
            return $rows;
        }

        $similar = $this->courseRepository->findSimilarCoursesFor($emptyCodes);

        return array_map(
            static fn (CourseOverviewRow $row): CourseOverviewRow => isset($similar[$row->code])
                ? $row->withSimilarCourses($similar[$row->code])
                : $row,
            $rows,
        );
    }

    private function currentFilter(): CourseFilter
    {
        return CourseFilter::tryFrom($this->filter) ?? CourseFilter::All;
    }

    private function currentSort(): CourseSort
    {
        return CourseSort::tryFrom($this->sort) ?? CourseSort::Code;
    }
}
