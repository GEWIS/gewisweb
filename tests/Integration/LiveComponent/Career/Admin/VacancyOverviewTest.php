<?php

declare(strict_types=1);

namespace App\Tests\Integration\LiveComponent\Career\Admin;

use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Career\Company;
use App\Entity\Career\Enums\VacancyCategories;
use App\Entity\Career\Vacancy;
use App\Repository\Career\CompanyRepository;
use App\Repository\Career\VacancyRepository;
use App\Tests\Integration\DatabaseTestCase;
use App\Twig\Components\Career\Admin\VacancyOverview;

use function array_map;
use function strval;

/**
 * The vacancies tab of the career overview. Every filter is a live prop rather than a query parameter read by the
 * controller, so they are exercised on a real instance with its real repositories. Each case builds its own component:
 * one instance answers for one set of filters (it holds on to its paginator), which is how a render uses it.
 */
final class VacancyOverviewTest extends DatabaseTestCase
{
    public function testWithoutFiltersEveryCompanysVacanciesAreListed(): void
    {
        $titles = $this->titles($this->overview(pageSize: 100));

        self::assertContains(
            'Backend Engineer',
            $titles,
        );
        self::assertContains(
            'Master Thesis: Explainable ML',
            $titles,
        );
    }

    public function testTheCategoryFilterNarrowsTheList(): void
    {
        $titles = $this->titles($this->overview(
            category: VacancyCategories::ThesisProjects,
            pageSize: 100,
        ));

        self::assertContains(
            'Master Thesis: Explainable ML',
            $titles,
        );
        self::assertNotContains(
            'Backend Engineer',
            $titles,
        );
    }

    /**
     * The list reads the working head, so a vacancy that has never been approved is listed too.
     */
    public function testTheStatusFilterNarrowsTheList(): void
    {
        self::assertContains(
            'Backend Engineer',
            $this->titles($this->overview(
                status: RevisionStatus::Approved,
                pageSize: 100,
            )),
        );
        self::assertNotContains(
            'Backend Engineer',
            $this->titles($this->overview(
                status: RevisionStatus::Submitted,
                pageSize: 100,
            )),
        );
    }

    /**
     * The empty option of the company `<select>` posts an empty value, which used to be read as an integer and threw.
     */
    public function testAnEmptyCompanyFilterLeavesTheListAlone(): void
    {
        $component = $this->overview(pageSize: 100);
        $component->companyFilter = '';

        self::assertContains(
            'Backend Engineer',
            $this->titles($component),
        );
    }

    public function testPickingACompanyNarrowsTheList(): void
    {
        $component = $this->overview(pageSize: 100);
        $component->companyFilter = strval($this->company('nexunt')->getId());

        foreach ($component->getVacancies() as $vacancy) {
            self::assertSame(
                'Nexunt Systems',
                $vacancy->getCompany()->getName(),
            );
        }
    }

    /**
     * Rendered on a company's own page the list is pinned to that company, and the company picker is not offered.
     */
    public function testPinningToACompanyOverridesThePicker(): void
    {
        $component = $this->overview(pageSize: 100);
        $component->company = $this->company('nexunt');
        $component->companyFilter = strval($this->company('orbit-analytics')->getId());

        self::assertTrue($component->isPinnedToACompany());
        foreach ($component->getVacancies() as $vacancy) {
            self::assertSame(
                'Nexunt Systems',
                $vacancy->getCompany()->getName(),
            );
        }
    }

    /**
     * @return list<string>
     */
    private function titles(VacancyOverview $component): array
    {
        return array_map(
            static fn (Vacancy $vacancy): string => strval($vacancy->getCurrentRevision()?->getName()->getValueEN()),
            $component->getVacancies(),
        );
    }

    private function company(string $slug): Company
    {
        $company = self::getContainer()->get(CompanyRepository::class)->findCompanyBySlugName($slug);
        self::assertInstanceOf(
            Company::class,
            $company,
        );

        return $company;
    }

    private function overview(
        ?RevisionStatus $status = null,
        ?VacancyCategories $category = null,
        int $pageSize = 10,
    ): VacancyOverview {
        $component = new VacancyOverview(
            self::getContainer()->get(VacancyRepository::class),
            self::getContainer()->get(CompanyRepository::class),
        );
        $component->status = $status?->value;
        $component->category = $category?->value;
        $component->pageSize = $pageSize;

        return $component;
    }
}
