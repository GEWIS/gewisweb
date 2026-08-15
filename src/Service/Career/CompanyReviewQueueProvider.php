<?php

declare(strict_types=1);

namespace App\Service\Career;

use App\Entity\Application\RevisionInterface;
use App\Entity\Career\CompanyRevision;
use App\Entity\User\Enums\UserRoles;
use App\Repository\Career\CompanyRevisionRepository;
use App\Service\Application\ReviewQueueProviderInterface;
use App\ViewModel\Application\ReviewQueueRow;
use App\ViewModel\Application\ReviewQueueSummary;
use Override;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

use function assert;
use function Symfony\Component\Translation\t;

/**
 * The company profiles waiting on the careers committee.
 */
#[AsTaggedItem(priority: 20)]
final readonly class CompanyReviewQueueProvider implements ReviewQueueProviderInterface
{
    public function __construct(
        private CompanyRevisionRepository $revisionRepository,
    ) {
    }

    #[Override]
    public function role(): UserRoles
    {
        return UserRoles::CompanyAdmin;
    }

    #[Override]
    public function queue(): ReviewQueueSummary
    {
        return new ReviewQueueSummary(
            key: 'companies',
            name: t('Companies'),
            icon: 'fa-briefcase',
            queueRoute: 'admin/career/approvals/index',
            rows: ReviewQueueRow::fromRevisions(
                $this->revisionRepository->findForReview(),
                static function (RevisionInterface $revision): string {
                    assert($revision instanceof CompanyRevision);

                    return $revision->getCompany()->getName();
                },
                'admin/career/approvals/company',
            ),
        );
    }
}
