<?php

declare(strict_types=1);

namespace App\ViewModel\Career\Portal;

use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Career\Company;
use App\Entity\Career\CompanyBannerPackage;
use App\Entity\Career\CompanyHighlightPackage;
use App\Entity\Career\CompanyPackage;
use App\Entity\Career\Vacancy;
use DateTime;

use function count;
use function usort;

/**
 * What a company sees when it signs in: where its profile stands, what its vacancies are doing, what runs out soon and
 * whether anything is waiting on somebody. Assembled once so the dashboard template does not work any of it out again
 * while rendering.
 */
final readonly class CompanyDashboard
{
    /**
     * @param list<Vacancy>        $liveVacancies    what the public can see right now
     * @param list<Vacancy>        $awaitingReview   with the committee
     * @param list<Vacancy>        $drafts           the company's own unfinished work
     * @param list<CompanyPackage> $expiringPackages running out within the horizon, soonest first
     */
    private function __construct(
        public Company $company,
        public ?RevisionStatus $profileStatus,
        public bool $profileNeedsAttention,
        public array $liveVacancies,
        public array $awaitingReview,
        public array $drafts,
        public array $expiringPackages,
        public bool $hasPendingBanner,
        public int $highlightedVacancies,
        public int $representatives,
    ) {
    }

    /**
     * @param list<Vacancy> $vacancies       every vacancy of the company, whatever state it is in
     * @param int           $representatives how many people can act for the company
     */
    public static function build(
        Company $company,
        array $vacancies,
        int $representatives,
        DateTime $expiringBefore,
    ): self {
        $live = [];
        $awaitingReview = [];
        $drafts = [];

        foreach ($vacancies as $vacancy) {
            $status = $vacancy->getCurrentRevision()?->getStatus();

            if ($vacancy->isActive()) {
                $live[] = $vacancy;
            }

            if (RevisionStatus::Draft === $status) {
                $drafts[] = $vacancy;
            } elseif (
                null !== $status
                && !$status->isTerminal()
            ) {
                $awaitingReview[] = $vacancy;
            }
        }

        $expiring = [];
        $hasPendingBanner = false;
        $highlighted = 0;

        foreach ($company->getPackages() as $package) {
            if (
                !$package->isExpired()
                && $package->getExpirationDate() <= $expiringBefore
            ) {
                $expiring[] = $package;
            }

            if (
                $package instanceof CompanyBannerPackage
                && $package->hasPendingImage()
            ) {
                $hasPendingBanner = true;
            }

            if (!$package instanceof CompanyHighlightPackage) {
                continue;
            }

            $highlighted += count($package->getDisplayableVacancies());
        }

        // The packages come out in whatever order the company happens to hold them, and what runs out this week
        // belongs above what runs out in three months.
        usort(
            $expiring,
            static function (
                CompanyPackage $a,
                CompanyPackage $b,
            ): int {
                return $a->getExpirationDate() <=> $b->getExpirationDate();
            },
        );

        $current = $company->getCurrentRevision();

        // A profile that has never been approved, or that came back with changes requested, is waiting on the company
        // rather than on anybody else. A request for changes is read off the draft that was spawned in answer to it,
        // since that draft is what the company is looking at by the time it gets here.
        $needsAttention = null === $company->getLiveRevision()
            || (
                null !== $current
                && RevisionStatus::Draft === $current->getStatus()
                && RevisionStatus::ChangesRequested === $current->getPreviousRevision()?->getStatus()
            );

        return new self(
            $company,
            $current?->getStatus(),
            $needsAttention,
            $live,
            $awaitingReview,
            $drafts,
            $expiring,
            $hasPendingBanner,
            $highlighted,
            $representatives,
        );
    }
}
