<?php

declare(strict_types=1);

namespace App\Service\Decision;

use App\Entity\Application\RevisionInterface;
use App\Entity\Decision\OrganInformationRevision;
use App\Entity\User\Enums\UserRoles;
use App\Repository\Decision\OrganInformationRevisionRepository;
use App\Service\Application\ReviewQueueProviderInterface;
use App\ViewModel\Application\ReviewQueueRow;
use App\ViewModel\Application\ReviewQueueSummary;
use Override;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

use function assert;
use function Symfony\Component\Translation\t;

/**
 * The body pages waiting on the board.
 */
#[AsTaggedItem(priority: 40)]
final readonly class OrganInformationReviewQueueProvider implements ReviewQueueProviderInterface
{
    public function __construct(
        private OrganInformationRevisionRepository $revisionRepository,
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
            key: 'bodies',
            name: t('Bodies'),
            icon: 'fa-sitemap',
            queueRoute: 'admin/decision/bodies/approvals/index',
            rows: ReviewQueueRow::fromRevisions(
                $this->revisionRepository->findForReview(),
                static function (RevisionInterface $revision): string {
                    assert($revision instanceof OrganInformationRevision);

                    return $revision->getOrgan()->getAbbr();
                },
                'admin/decision/bodies/approvals/review',
            ),
        );
    }
}
