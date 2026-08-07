<?php

declare(strict_types=1);

namespace App\Twig\Components\Education\Admin;

use App\Entity\Education\Course;
use App\Entity\Education\Enums\CourseFilter;
use App\Entity\User\Enums\UserRoles;
use App\Repository\Education\CourseRepository;
use App\Twig\Components\Application\AbstractPaginatedOverview;
use App\ViewModel\Education\CourseOverviewRow;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Override;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

use function array_map;

/**
 * How much material a course has is counted for the visible page in one query rather than read off each course, so the
 * table costs two queries whatever the page size.
 *
 * @extends AbstractPaginatedOverview<Course>
 */
#[AsLiveComponent(
    name: 'Education:Admin:CourseOverview',
    template: 'components/Education/Admin/CourseOverview.html.twig',
)]
#[IsGranted(UserRoles::Board->value)]
final class CourseOverview extends AbstractPaginatedOverview
{
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
    public string $filter = CourseFilter::All->value;

    /** @var array<string, CourseOverviewRow>|null */
    private ?array $counts = null;

    public function __construct(private readonly CourseRepository $courseRepository)
    {
    }

    public function onFilterUpdated(): void
    {
        $this->resetToFirstPage();
    }

    /**
     * @return list<Course>
     */
    public function getCourses(): array
    {
        return $this->getRows();
    }

    /**
     * @return array<string, CourseOverviewRow>
     */
    public function getCounts(): array
    {
        return $this->counts ??= $this->courseRepository->countsFor(array_map(
            static fn (Course $course): string => $course->getCode(),
            $this->getCourses(),
        ));
    }

    /**
     * @return CourseFilter[]
     */
    public function getFilters(): array
    {
        return CourseFilter::cases();
    }

    /**
     * @return Paginator<Course>
     */
    #[Override]
    protected function createPaginator(
        int $page,
        int $pageSize,
    ): Paginator {
        return $this->courseRepository->paginateForAdmin(
            search: $this->search,
            filter: CourseFilter::tryFrom($this->filter) ?? CourseFilter::All,
            page: $page,
            pageSize: $pageSize,
        );
    }
}
