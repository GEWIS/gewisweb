<?php

declare(strict_types=1);

namespace App\Service\Career;

use App\Entity\Application\Enums\NotificationType;
use App\Entity\Application\RevisionInterface;
use App\Entity\Career\CompanyRevision;
use App\Entity\Career\VacancyRevision;
use App\Entity\User\Enums\UserRoles;
use App\Service\Application\RevisionNotificationInterface;
use Override;
use RuntimeException;

use function sprintf;

/**
 * What a company puts forward is C4's to look at, and the board sees it too because its own role reaches through. Both
 * career aggregates answer here, so a profile and a vacancy stay distinguishable in the notification centre.
 */
final readonly class CareerRevisionNotification implements RevisionNotificationInterface
{
    #[Override]
    public function supports(RevisionInterface $revision): bool
    {
        return $revision instanceof CompanyRevision
            || $revision instanceof VacancyRevision;
    }

    #[Override]
    public function awaitingReviewType(RevisionInterface $revision): NotificationType
    {
        return match (true) {
            $revision instanceof CompanyRevision => NotificationType::CompanyRevisionAwaitingReview,
            $revision instanceof VacancyRevision => NotificationType::VacancyRevisionAwaitingReview,
            default => throw new RuntimeException(sprintf(
                'A career notification cannot be raised for "%s".',
                $revision::class,
            )),
        };
    }

    #[Override]
    public function audienceRole(RevisionInterface $revision): UserRoles
    {
        return UserRoles::CompanyAdmin;
    }
}
