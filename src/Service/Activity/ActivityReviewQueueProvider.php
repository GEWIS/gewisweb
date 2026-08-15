<?php

declare(strict_types=1);

namespace App\Service\Activity;

use App\Entity\Activity\ActivityRevision;
use App\Entity\Application\Enums\Languages;
use App\Entity\Application\RevisionInterface;
use App\Entity\User\Enums\UserRoles;
use App\Repository\Activity\ActivityRevisionRepository;
use App\Service\Application\ReviewQueueProviderInterface;
use App\ViewModel\Application\ReviewQueueRow;
use App\ViewModel\Application\ReviewQueueSummary;
use Override;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

use function assert;
use function strval;
use function Symfony\Component\Translation\t;

/**
 * The activities waiting on the board.
 */
#[AsTaggedItem(priority: 50)]
final readonly class ActivityReviewQueueProvider implements ReviewQueueProviderInterface
{
    public function __construct(
        private ActivityRevisionRepository $revisionRepository,
    ) {
    }

    #[Override]
    public function role(): UserRoles
    {
        return UserRoles::Board;
    }

    #[Override]
    public function queue(): ReviewQueueSummary
    {
        return new ReviewQueueSummary(
            key: 'activities',
            name: t('Activities'),
            icon: 'fa-calendar-days',
            queueRoute: 'admin/activities/approvals/index',
            rows: ReviewQueueRow::fromRevisions(
                $this->revisionRepository->findForReview(),
                static function (RevisionInterface $revision): string {
                    assert($revision instanceof ActivityRevision);

                    return strval($revision->getName()->getText(Languages::current()));
                },
                'admin/activities/approvals/review',
            ),
        );
    }
}
