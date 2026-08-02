<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository\Career;

use App\Entity\Career\Company;
use App\Entity\Career\CompanyHighlightPackage;
use App\Repository\Career\CompanyHighlightPackageRepository;
use App\Repository\Career\CompanyRepository;
use App\Repository\Career\VacancyRepository;
use App\Tests\Integration\DatabaseTestCase;
use DateTime;

final class CompanyHighlightPackageTest extends DatabaseTestCase
{
    public function testOnlyACompanysOwnLiveVacanciesMayBeHighlighted(): void
    {
        $company = $this->company('nexunt');

        $slugs = [];
        foreach ($this->vacancies()->findHighlightableForCompany($company) as $vacancy) {
            $slugs[] = $vacancy->getSlugName();
            self::assertSame(
                $company,
                $vacancy->getCompany(),
            );
        }

        self::assertContains(
            'backend-engineer',
            $slugs,
        );
        // Its posting window closed, so it cannot be put on the landing page.
        self::assertNotContains(
            'platform-internship',
            $slugs,
        );
        self::assertNotContains(
            'ml-thesis',
            $slugs,
        );
    }

    /**
     * There is deliberately no cap per category, so a company may highlight everything it has running.
     */
    public function testThereIsNoLimitOnHowManyOfEachCategoryMayBeHighlighted(): void
    {
        $company = $this->company('delta-robotics');
        $highlightable = $this->vacancies()->findHighlightableForCompany($company);

        $package = new CompanyHighlightPackage();
        $package->setCompany($company);
        $package->setStartingDate(new DateTime('-1 month'));
        $package->setExpirationDate(new DateTime('+1 year'));
        $package->setPublished(true);
        $package->setVacancies($highlightable);
        $this->entityManager->persist($package);
        $this->entityManager->flush();

        self::assertCount(
            3,
            $package->getDisplayableVacancies(),
        );
    }

    public function testAPickThatStopsBeingLiveDropsOutOfTheHighlights(): void
    {
        $package = $this->seededHighlightPackage();
        $before = $package->getDisplayableVacancies();
        self::assertNotEmpty($before);

        $dropped = $before[0];
        $dropped->setPublished(false);
        $this->entityManager->flush();

        self::assertNotContains(
            $dropped,
            $package->getDisplayableVacancies(),
        );
        // The pick itself is untouched; only what the landing page shows changes.
        self::assertTrue($package->getVacancies()->contains($dropped));
    }

    public function testAnExpiredHighlightPackageShowsNothing(): void
    {
        $package = $this->seededHighlightPackage();
        $package->setExpirationDate(new DateTime('-1 day'));
        $this->entityManager->flush();

        self::assertSame(
            [],
            $package->getDisplayableVacancies(),
        );
    }

    public function testTheLandingPageOnlyAsksForPackagesThatAreRunning(): void
    {
        $active = $this->highlightPackages()->findActive();
        self::assertNotEmpty($active);

        $active[0]->setPublished(false);
        $this->entityManager->flush();

        self::assertNotContains(
            $active[0],
            $this->highlightPackages()->findActive(),
        );
    }

    private function seededHighlightPackage(): CompanyHighlightPackage
    {
        $packages = $this->highlightPackages()->findActive();
        self::assertNotEmpty($packages);

        return $packages[0];
    }

    private function company(string $slug): Company
    {
        $company = self::getContainer()->get(CompanyRepository::class)->findOneBy(['slugName' => $slug]);
        self::assertInstanceOf(
            Company::class,
            $company,
        );

        return $company;
    }

    private function vacancies(): VacancyRepository
    {
        return self::getContainer()->get(VacancyRepository::class);
    }

    private function highlightPackages(): CompanyHighlightPackageRepository
    {
        return self::getContainer()->get(CompanyHighlightPackageRepository::class);
    }
}
