<?php

declare(strict_types=1);

namespace App\Twig\Components\Activity;

use App\Entity\Activity\Activity;
use App\Entity\Activity\ActivityLabel;
use App\Entity\Activity\Enums\ActivityCategories;
use App\Entity\Decision\AssociationYear;
use App\Entity\Decision\Member;
use App\Entity\Decision\Organ;
use App\Entity\User\User;
use App\Repository\Activity\ActivityLabelRepository;
use App\Repository\Activity\ActivityRepository;
use DateTime;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Metadata\UrlMapping;

use function array_filter;
use function array_map;
use function array_values;
use function count;
use function iterator_to_array;
use function trim;

/**
 * Backs the four activity overview pages: upcoming/archive × public/subscribed. The page fixes `subscribed` and `past`
 * at mount time; everything else is a live filter. Infinite scroll grows `limit` via the loadMore action.
 */
#[AsLiveComponent(
    name: 'Activity:ActivityOverview',
    template: 'components/Activity/ActivityOverview.html.twig',
)]
final class ActivityOverview
{
    use DefaultActionTrait;

    public const int PAGE_SIZE = 15;

    #[LiveProp]
    public bool $subscribed = false;

    #[LiveProp]
    public bool $past = false;

    // All the filters mirror themselves into the query string via the History API, so the state survives a reload and
    // the address bar is itself a shareable link (the copy button just yields the same URL in one click).
    #[LiveProp(
        writable: true,
        url: true,
    )]
    public ?string $category = null;

    // The association year is part of the page path (/archive/{year}), set at mount time; not a live filter.
    #[LiveProp]
    public ?int $year = null;

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

    #[LiveProp(
        writable: true,
        url: new UrlMapping(as: 'organ'),
    )]
    public ?int $organFilter = null;

    #[LiveProp(
        writable: true,
        url: new UrlMapping(as: 'openSignup'),
    )]
    public bool $openSignupOnly = false;

    #[LiveProp(
        writable: true,
        url: new UrlMapping(as: 'from'),
    )]
    public ?string $fromDate = null;

    #[LiveProp(
        writable: true,
        url: new UrlMapping(as: 'until'),
    )]
    public ?string $untilDate = null;

    // Pagination state: not URL-synced and not client-writable. Only the `loadMore` action grows it server-side, so a
    // crafted request cannot ask for an arbitrarily large page.
    #[LiveProp]
    public int $limit = self::PAGE_SIZE;

    /** @var Paginator<Activity>|null */
    private ?Paginator $paginator = null;

    /** @var Activity[]|null */
    private ?array $activities = null;

    private bool $memberResolved = false;
    private ?Member $member = null;

    public function __construct(
        private readonly ActivityRepository $activityRepository,
        private readonly ActivityLabelRepository $activityLabelRepository,
        private readonly Security $security,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[LiveAction]
    public function loadMore(): void
    {
        $this->limit += self::PAGE_SIZE;
    }

    /**
     * @return Activity[]
     */
    public function getActivities(): array
    {
        if (null !== $this->activities) {
            return $this->activities;
        }

        if (!$this->canQuery()) {
            return $this->activities = [];
        }

        $activities = iterator_to_array(
            $this->getPaginator()->getIterator(),
            false,
        );

        // Hydrate the sign-up lists for the whole page in one query so the per-item accessors do not N+1.
        $this->activityRepository->primeSignupLists($activities);

        return $this->activities = $activities;
    }

    /**
     * The activities grouped by association-year string (e.g. '2025-2026'), preserving the `DESC` order for archives.
     *
     * @return array<string, Activity[]>
     */
    public function getActivitiesByAssociationYear(): array
    {
        $grouped = [];
        foreach ($this->getActivities() as $activity) {
            $grouped[AssociationYear::fromDate($activity->getBeginTime())->getYearString()][] = $activity;
        }

        return $grouped;
    }

    public function getTotalCount(): int
    {
        if (!$this->canQuery()) {
            return 0;
        }

        return $this->getPaginator()->count();
    }

    public function hasMore(): bool
    {
        return $this->getTotalCount() > count($this->getActivities());
    }

    /**
     * @return ActivityLabel[]
     */
    public function getLabels(): array
    {
        return $this->activityLabelRepository->findBy(
            [],
            ['id' => 'ASC'],
        );
    }

    /**
     * @return Organ[]
     */
    public function getOrgans(): array
    {
        return $this->activityRepository->findOrganisingOrgans();
    }

    /**
     * @return ActivityCategories[]
     */
    public function getCategories(): array
    {
        return ActivityCategories::selectableCases();
    }

    /**
     * Whether a query should run at all: the subscribed pages need an authenticated member.
     */
    private function canQuery(): bool
    {
        return !$this->subscribed || null !== $this->currentMember();
    }

    /**
     * @return Paginator<Activity>
     */
    private function getPaginator(): Paginator
    {
        return $this->paginator ??= $this->activityRepository->findForOverview(
            past: $this->past,
            subscribedBy: $this->subscribed ? $this->currentMember() : null,
            search: $this->search,
            locale: $this->requestStack->getCurrentRequest()?->getLocale() ?? 'en',
            category: null !== $this->category
                ? ActivityCategories::tryFrom($this->category)
                : null,
            labelIds: $this->selectedLabelIds(),
            organId: $this->organFilter,
            openSignupOnly: $this->openSignupOnly,
            from: $this->effectiveFrom(),
            until: $this->effectiveUntil(),
            limit: $this->limit,
            offset: 0,
        );
    }

    /**
     * @return int[]
     */
    private function selectedLabelIds(): array
    {
        return array_values(
            array_filter(
                array_map(
                    'intval',
                    $this->labelFilters,
                ),
                static fn (int $id): bool => $id > 0,
            ),
        );
    }

    /**
     * The effective start of the time window: an explicit "from" filter, otherwise the start of the selected
     * association year (archive), otherwise unbounded.
     */
    private function effectiveFrom(): ?DateTime
    {
        $explicit = $this->parseDate($this->fromDate);
        if (null !== $explicit) {
            return $explicit;
        }

        return null !== $this->year
            ? AssociationYear::fromYear($this->year)->getStartDate()
            : null;
    }

    private function effectiveUntil(): ?DateTime
    {
        $explicit = $this->parseDate(
            $this->untilDate,
            true,
        );
        if (null !== $explicit) {
            return $explicit;
        }

        return null !== $this->year
            ? AssociationYear::fromYear($this->year)->getEndDate()
            : null;
    }

    private function currentMember(): ?Member
    {
        if (!$this->memberResolved) {
            $this->memberResolved = true;
            $user = $this->security->getUser();
            $this->member = $user instanceof User
                ? $user->getMember()
                : null;
        }

        return $this->member;
    }

    private function parseDate(
        ?string $value,
        bool $endOfDay = false,
    ): ?DateTime {
        if (
            null === $value
            || '' === trim($value)
        ) {
            return null;
        }

        $date = DateTime::createFromFormat(
            'Y-m-d',
            $value,
        );

        if (false === $date) {
            return null;
        }

        return $endOfDay
            ? $date->setTime(
                23,
                59,
                59,
            )
            : $date->setTime(
                0,
                0,
            );
    }
}
