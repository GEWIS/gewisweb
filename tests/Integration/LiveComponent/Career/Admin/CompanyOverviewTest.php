<?php

declare(strict_types=1);

namespace App\Tests\Integration\LiveComponent\Career\Admin;

use App\Entity\Career\Company;
use App\Repository\Career\CompanyRepository;
use App\Tests\Integration\DatabaseTestCase;
use App\Twig\Components\Career\Admin\CompanyOverview;

use function array_intersect;
use function array_map;
use function sprintf;

/**
 * The companies tab of the career overview. Searching and paging happen in the component rather than the controller,
 * so they are exercised on a real instance with its real repository. Each case builds its own component: one instance
 * answers for one set of filters (it holds on to its paginator), which is exactly how a render uses it.
 */
final class CompanyOverviewTest extends DatabaseTestCase
{
    public function testTheSearchMatchesOnTheNameAndOnTheSlug(): void
    {
        self::assertSame(
            ['Nexunt Systems'],
            $this->names($this->overview(search: 'nexunt')),
        );
        self::assertSame(
            ['Orbit Analytics'],
            $this->names($this->overview(search: 'orbit-analytics')),
        );
    }

    public function testACompanyThePublicCannotSeeIsStillListed(): void
    {
        self::assertContains(
            'Halcyon Mobility',
            $this->names($this->overview()),
        );
    }

    public function testTheListIsPagedWhileTheCountCoversEverything(): void
    {
        $seeded = $this->overview()->getTotalCount();
        $this->seedCompanies(15);

        $firstPage = $this->overview();
        self::assertCount(
            10,
            $firstPage->getCompanies(),
        );
        self::assertSame(
            $seeded + 15,
            $firstPage->getTotalCount(),
        );
        self::assertGreaterThan(
            1,
            $firstPage->getTotalPages(),
        );

        $secondPage = $this->overview(page: 2);
        self::assertNotEmpty($secondPage->getCompanies());
        self::assertEmpty(array_intersect(
            $this->names($firstPage),
            $this->names($secondPage),
        ));
    }

    /**
     * Narrowing the search from a later page must not leave the reader stranded on a page that no longer exists.
     */
    public function testSearchingReturnsToTheFirstPage(): void
    {
        $component = $this->overview(page: 2);

        $component->search = 'nexunt';
        $component->onSearchUpdated();

        self::assertSame(
            1,
            $component->page,
        );
    }

    /**
     * The seed holds a handful of companies, too few to page through, so this adds enough to fill a second page.
     */
    private function seedCompanies(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $company = new Company();
            $company->setName(sprintf('Paging Test %02d', $i));
            $company->setSlugName(sprintf('paging-test-%02d', $i));
            $company->setPublished(false);
            $this->entityManager->persist($company);
        }

        $this->entityManager->flush();
    }

    /**
     * @return list<string>
     */
    private function names(CompanyOverview $component): array
    {
        return array_map(
            static fn (Company $company): string => $company->getName(),
            $component->getCompanies(),
        );
    }

    private function overview(
        string $search = '',
        int $pageSize = 10,
        int $page = 1,
    ): CompanyOverview {
        $component = new CompanyOverview(self::getContainer()->get(CompanyRepository::class));
        $component->search = $search;
        $component->pageSize = $pageSize;
        $component->page = $page;

        return $component;
    }
}
