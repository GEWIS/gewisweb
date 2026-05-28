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

    public const int PAGE_SIZE = 20;

    #[LiveProp]
    public bool $subscribed = false;

    #[LiveProp]
    public bool $past = false;

    #[LiveProp(writable: true)]
    public string $search = '';

    #[LiveProp(writable: true)]
    public ?string $categoryFilter = null;

    /** @var int[] */
    #[LiveProp(writable: true)]
    public array $labelFilters = [];

    #[LiveProp(writable: true)]
    public ?int $organFilter = null;

    #[LiveProp(writable: true)]
    public bool $openSignupOnly = false;

    #[LiveProp(writable: true)]
    public ?string $fromDate = null;

    #[LiveProp(writable: true)]
    public ?string $untilDate = null;

    #[LiveProp(writable: true)]
    public int $limit = self::PAGE_SIZE;

    /** @var Paginator<Activity>|null */
    private ?Paginator $paginator = null;

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
        if (!$this->canQuery()) {
            return [];
        }

        return iterator_to_array(
            $this->getPaginator()->getIterator(),
            false,
        );
    }

    /**
     * The activities grouped by association-year string (e.g. '2025-2026'), preserving the DESC order — for archives.
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
     * @return ActivityCategories[]
     */
    public function getCategories(): array
    {
        return ActivityCategories::cases();
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
            category: null !== $this->categoryFilter
                ? ActivityCategories::tryFrom($this->categoryFilter)
                : null,
            labelIds: array_values(
                array_filter(
                    array_map(
                        'intval',
                        $this->labelFilters,
                    ),
                    static fn (int $id): bool => $id > 0,
                ),
            ),
            organId: $this->organFilter,
            openSignupOnly: $this->openSignupOnly,
            from: $this->parseDate($this->fromDate),
            until: $this->parseDate(
                $this->untilDate,
                true,
            ),
            limit: $this->limit,
            offset: 0,
        );
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
