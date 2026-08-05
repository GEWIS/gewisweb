<?php

declare(strict_types=1);

namespace App\ViewModel\Career\Portal;

use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Career\Vacancy;

use function array_key_exists;
use function count;

/**
 * A company's vacancies split over the tabs of its own overview: what is on the website, what is with the committee,
 * what it has not finished, and what has run its course. A vacancy sits in one tab only, the one that says what has to
 * happen to it next, so a live posting with an unsubmitted draft over it is filed under drafts.
 */
final readonly class CompanyVacancyOverview
{
    public const string ALL = 'all';
    public const string LIVE = 'live';
    public const string REVIEW = 'review';
    public const string DRAFTS = 'drafts';
    public const string CLOSED = 'closed';

    /**
     * @param array<string, list<Vacancy>> $groups    every tab's vacancies, keyed by tab, in the order they are shown
     * @param array<string, int>           $counts    how many sit behind every tab
     * @param list<Vacancy>                $vacancies the ones in the chosen tab
     */
    private function __construct(
        public string $filter,
        public array $groups,
        public array $counts,
        public array $vacancies,
    ) {
    }

    /**
     * @param list<Vacancy> $vacancies every vacancy of the company, whatever state it is in
     * @param string        $filter    the tab asked for, which falls back to all for anything unknown
     */
    public static function build(
        array $vacancies,
        string $filter = self::ALL,
    ): self {
        $groups = [
            self::ALL => $vacancies,
            self::LIVE => [],
            self::REVIEW => [],
            self::DRAFTS => [],
            self::CLOSED => [],
        ];

        foreach ($vacancies as $vacancy) {
            $groups[self::groupOf($vacancy)][] = $vacancy;
        }

        $counts = [];
        foreach ($groups as $group => $items) {
            $counts[$group] = count($items);
        }

        if (
            !array_key_exists(
                $filter,
                $groups,
            )
        ) {
            $filter = self::ALL;
        }

        return new self(
            $filter,
            $groups,
            $counts,
            $groups[$filter],
        );
    }

    private static function groupOf(Vacancy $vacancy): string
    {
        $status = $vacancy->getCurrentRevision()?->getStatus();

        if (RevisionStatus::Draft === $status) {
            return self::DRAFTS;
        }

        if (
            null !== $status
            && !$status->isTerminal()
        ) {
            return self::REVIEW;
        }

        return $vacancy->isActive()
            ? self::LIVE
            : self::CLOSED;
    }
}
