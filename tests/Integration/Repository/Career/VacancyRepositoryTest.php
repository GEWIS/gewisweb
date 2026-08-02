<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository\Career;

use App\Entity\Career\Enums\VacancyCategories;
use App\Entity\Career\Vacancy;
use App\Repository\Career\VacancyRepository;
use App\Tests\Integration\DatabaseTestCase;
use DateTime;

final class VacancyRepositoryTest extends DatabaseTestCase
{
    /**
     * The seed's platform internship is approved and published, but its window closed in 2025.
     */
    public function testAVacancyWhoseWindowHasClosedIsLeftOutOfTheOverview(): void
    {
        self::assertNotContains(
            'platform-internship',
            $this->slugsOnTheOverview(),
        );
    }

    /**
     * A vacancy that says nothing about when it closes runs until its package does.
     */
    public function testAVacancyInheritsItsPackagesExpiryAsItsClosingDay(): void
    {
        $vacancy = $this->vacancy('data-science-internship');

        self::assertContains(
            'data-science-internship',
            $this->slugsOnTheOverview(),
        );
        self::assertSame(
            $vacancy->getPackage()->getExpirationDate()->format('Y-m-d'),
            $vacancy->getEndDate()->format('Y-m-d'),
        );
    }

    public function testAVacancyWhoseWindowHasNotOpenedYetIsLeftOut(): void
    {
        $vacancy = $this->vacancy('backend-engineer');
        $live = $vacancy->getLiveRevision();
        self::assertNotNull($live);

        $live->setStartDate(new DateTime('+1 week'));
        $this->entityManager->flush();

        self::assertNotContains(
            'backend-engineer',
            $this->slugsOnTheOverview(),
        );
    }

    public function testTheDetailPageIsGoneOnceTheWindowCloses(): void
    {
        self::assertNotNull($this->publicVacancy('backend-engineer', VacancyCategories::Jobs));

        $live = $this->vacancy('backend-engineer')->getLiveRevision();
        self::assertNotNull($live);
        $live->setEndDate(new DateTime('-1 day'));
        $this->entityManager->flush();

        self::assertNull($this->publicVacancy('backend-engineer', VacancyCategories::Jobs));
    }

    /**
     * The window applies wherever "active" is worked out, not only in the queries.
     */
    public function testAClosedWindowAlsoMakesTheVacancyInactiveInMemory(): void
    {
        $vacancy = $this->vacancy('platform-internship');

        self::assertFalse($vacancy->isActive());
    }

    /**
     * @return list<string>
     */
    private function slugsOnTheOverview(): array
    {
        $slugs = [];
        foreach ($this->repository()->findForOverview() as $vacancy) {
            $slugs[] = $vacancy->getSlugName();
        }

        return $slugs;
    }

    private function publicVacancy(
        string $slug,
        VacancyCategories $category,
    ): ?Vacancy {
        return $this->repository()->findPublicVacancy(
            'nexunt',
            $category,
            $slug,
        );
    }

    private function vacancy(string $slug): Vacancy
    {
        $vacancy = $this->entityManager->getRepository(Vacancy::class)->findOneBy(['slugName' => $slug]);
        self::assertInstanceOf(
            Vacancy::class,
            $vacancy,
        );

        return $vacancy;
    }

    private function repository(): VacancyRepository
    {
        return self::getContainer()->get(VacancyRepository::class);
    }
}
