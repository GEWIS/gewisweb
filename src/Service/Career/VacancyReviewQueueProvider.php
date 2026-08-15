<?php

declare(strict_types=1);

namespace App\Service\Career;

use App\Entity\Application\RevisionInterface;
use App\Entity\Career\VacancyRevision;
use App\Entity\User\Enums\UserRoles;
use App\Repository\Career\VacancyRevisionRepository;
use App\Service\Application\ReviewQueueProviderInterface;
use App\ViewModel\Application\ReviewQueueRow;
use App\ViewModel\Application\ReviewQueueSummary;
use Override;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

use function assert;
use function Symfony\Component\Translation\t;

/**
 * The vacancies waiting on the careers committee.
 */
#[AsTaggedItem(priority: 10)]
final readonly class VacancyReviewQueueProvider implements ReviewQueueProviderInterface
{
    public function __construct(
        private VacancyRevisionRepository $revisionRepository,
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
            key: 'vacancies',
            name: t('Vacancies'),
            icon: 'fa-file-lines',
            queueRoute: 'admin/career/approvals/index',
            rows: ReviewQueueRow::fromRevisions(
                $this->revisionRepository->findForReview(),
                static function (RevisionInterface $revision): string {
                    assert($revision instanceof VacancyRevision);

                    return $revision->getVacancy()->getSlugName();
                },
                'admin/career/approvals/vacancy',
            ),
        );
    }
}
