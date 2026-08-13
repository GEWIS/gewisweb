<?php

declare(strict_types=1);

namespace App\Controller\Frontpage;

use App\Entity\User\Enums\UserRoles;
use App\Repository\Application\AnnouncementRepository;
use App\Service\Application\MaintenanceStatusProvider;
use App\Service\Application\ReviewQueueProviderInterface;
use App\ViewModel\Application\ReviewQueueRow;
use App\ViewModel\Application\ReviewQueueSummary;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

use function array_map;
use function array_slice;
use function array_sum;
use function count;
use function usort;

/**
 * Where an administrator lands: what is waiting on them, what the whole association is being told at the moment, and
 * the way on to the parts of the administration they may use.
 *
 * Everything waiting is shown as one list rather than a queue per module, because what an administrator wants to know
 * is what to deal with next, not which part of the site it came from. The reader can narrow that list to a single
 * area, which is also the way on to that area's own queue.
 *
 * The page is reachable by any active member, so every block below is gated on its own. A member with no queues sees
 * the areas they can manage and nothing else, rather than an empty dashboard implying there is nothing to do.
 */
#[Route(
    path: '/admin',
    name: 'admin/',
)]
class AdminController extends AbstractController
{
    private const int OLDEST_LIMIT = 8;

    /**
     * @param iterable<ReviewQueueProviderInterface> $queueProviders
     */
    public function __construct(
        #[AutowireIterator('app.review_queue')]
        private readonly iterable $queueProviders,
        private readonly AnnouncementRepository $announcementRepository,
        private readonly MaintenanceStatusProvider $maintenanceStatusProvider,
    ) {
    }

    #[Route(
        path: '',
        name: 'index',
    )]
    public function index(
        #[MapQueryParameter]
        ?string $queue = null,
    ): Response {
        $queues = $this->queues();
        $isAdmin = $this->isGranted(UserRoles::Admin->value);

        // An area nobody has heard of, or one this reader may not deal with, reads as no filter at all.
        $selected = null;
        foreach ($queues as $summary) {
            if ($summary->key !== $queue) {
                continue;
            }

            $selected = $summary;
        }

        $waiting = $this->waiting(
            $queues,
            $selected,
        );

        return $this->render(
            'frontpage/admin/index.html.twig',
            [
                'queues' => $queues,
                'selected' => $selected,
                'waiting' => array_slice(
                    $waiting,
                    0,
                    self::OLDEST_LIMIT,
                ),
                'waitingCount' => count($waiting),
                'totalCount' => array_sum(array_map(
                    static fn (ReviewQueueSummary $summary): int => count($summary->rows),
                    $queues,
                )),
                'oldest' => $waiting[0]['row']->submittedAt ?? null,
                'announcements' => $isAdmin
                    ? $this->announcementRepository->findActive(new DateTimeImmutable())
                    : [],
                'maintenance' => $isAdmin
                    ? $this->maintenanceStatusProvider->status()
                    : null,
                'showsAdministration' => $isAdmin,
            ],
        );
    }

    /**
     * The queues this reader may deal with: every tagged domain queue whose role they hold.
     *
     * @return list<ReviewQueueSummary>
     */
    private function queues(): array
    {
        $queues = [];
        foreach ($this->queueProviders as $provider) {
            if (!$this->isGranted($provider->role()->value)) {
                continue;
            }

            $queues[] = $provider->queue();
        }

        return $queues;
    }

    /**
     * Everything waiting on this reader as one list, oldest first, each line still saying which queue it came from.
     *
     * @param list<ReviewQueueSummary> $queues
     *
     * @return list<array{queue: ReviewQueueSummary, row: ReviewQueueRow}>
     */
    private function waiting(
        array $queues,
        ?ReviewQueueSummary $selected,
    ): array {
        $waiting = [];
        foreach ($queues as $summary) {
            if (
                null !== $selected
                && $summary->key !== $selected->key
            ) {
                continue;
            }

            foreach ($summary->rows as $row) {
                $waiting[] = [
                    'queue' => $summary,
                    'row' => $row,
                ];
            }
        }

        usort(
            $waiting,
            /**
             * @param array{queue: ReviewQueueSummary, row: ReviewQueueRow} $a
             * @param array{queue: ReviewQueueSummary, row: ReviewQueueRow} $b
             */
            static fn (
                array $a,
                array $b,
            ): int => $a['row']->submittedAt <=> $b['row']->submittedAt,
        );

        return $waiting;
    }
}
