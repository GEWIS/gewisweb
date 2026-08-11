<?php

declare(strict_types=1);

namespace App\Tests\Integration\LiveComponent\Application;

use App\Entity\Career\Company;
use App\Repository\Career\CompanyRepository;
use App\Tests\Integration\DatabaseTestCase;
use App\Twig\Components\Career\Admin\CompanyOverview;

use function array_intersect;
use function array_map;
use function min;
use function sprintf;

/**
 * The paging shared by every administrative overview, exercised through one of them. Clamping used to be written out
 * in each component and tested in none, so a component that got it wrong would happily render an empty page or divide
 * by a page size nobody offered.
 */
final class PaginatedOverviewTest extends DatabaseTestCase
{
    public function testGoingToAPageBeyondTheLastOneStopsAtTheLastOne(): void
    {
        $this->seedCompanies(15);
        $overview = $this->overview();

        $overview->gotoPage(999);

        self::assertSame(
            $overview->getTotalPages(),
            $overview->page,
        );
        self::assertNotEmpty($overview->getRows());
    }

    /**
     * The query is worked out once per instance and working out the last page already runs it, so a page asked for
     * after that has to reach the query as well: the same instance renders the answer.
     */
    public function testGoingToAPageServesThatPagesRows(): void
    {
        $this->seedCompanies(15);
        $overview = $this->overview();
        $firstPage = $this->names($overview);
        $pageSize = CompanyOverview::PAGE_SIZES[0];
        $expected = min(
            $pageSize,
            $overview->getTotalCount() - $pageSize,
        );

        $overview->gotoPage(2);

        self::assertSame(
            2,
            $overview->page,
        );
        self::assertCount(
            $expected,
            $overview->getRows(),
        );
        self::assertEmpty(array_intersect(
            $firstPage,
            $this->names($overview),
        ));
    }

    public function testGoingToAPageBeforeTheFirstOneStopsAtTheFirstOne(): void
    {
        $overview = $this->overview();

        $overview->gotoPage(-3);

        self::assertSame(
            1,
            $overview->page,
        );
    }

    /**
     * The page size is client-writable, so a value nobody offered must not reach the query.
     */
    public function testAPageSizeOutsideTheOfferedStepsFallsBackToTheSmallest(): void
    {
        $this->seedCompanies(15);
        $overview = $this->overview();
        $overview->pageSize = 7;

        self::assertCount(
            CompanyOverview::PAGE_SIZES[0],
            $overview->getRows(),
        );
    }

    public function testNarrowingTheListReturnsToTheFirstPage(): void
    {
        $this->seedCompanies(25);
        $overview = $this->overview();
        $overview->gotoPage(3);
        self::assertGreaterThan(
            1,
            $overview->page,
        );

        $overview->search = 'nexunt';
        $overview->onSearchUpdated();

        self::assertSame(
            1,
            $overview->page,
        );
    }

    /**
     * @return list<string>
     */
    private function names(CompanyOverview $overview): array
    {
        return array_map(
            static fn (Company $company): string => $company->getName(),
            $overview->getRows(),
        );
    }

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

    private function overview(): CompanyOverview
    {
        return new CompanyOverview(self::getContainer()->get(CompanyRepository::class));
    }
}
