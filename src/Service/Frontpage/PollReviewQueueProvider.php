<?php

declare(strict_types=1);

namespace App\Service\Frontpage;

use App\Entity\Application\Enums\Languages;
use App\Entity\Application\RevisionInterface;
use App\Entity\Frontpage\PollRevision;
use App\Entity\User\Enums\UserRoles;
use App\Repository\Frontpage\PollRevisionRepository;
use App\Service\Application\ReviewQueueProviderInterface;
use App\ViewModel\Application\ReviewQueueRow;
use App\ViewModel\Application\ReviewQueueSummary;
use Override;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

use function assert;
use function Symfony\Component\Translation\t;

/**
 * The poll questions waiting on the board.
 */
#[AsTaggedItem(priority: 30)]
final readonly class PollReviewQueueProvider implements ReviewQueueProviderInterface
{
    public function __construct(
        private PollRevisionRepository $revisionRepository,
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
            key: 'polls',
            name: t('Polls'),
            icon: 'fa-square-poll-vertical',
            queueRoute: 'admin/frontpage/polls/approvals/index',
            rows: ReviewQueueRow::fromRevisions(
                $this->revisionRepository->findForReview(),
                static function (RevisionInterface $revision): string {
                    assert($revision instanceof PollRevision);

                    return $revision->getQuestion()->getText(Languages::current()) ?? '';
                },
                'admin/frontpage/polls/approvals/review',
            ),
        );
    }
}
