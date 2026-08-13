<?php

declare(strict_types=1);

namespace App\Service\Activity;

use App\Entity\Activity\ActivityRevision;
use App\Entity\Application\Enums\NotificationType;
use App\Entity\Application\RevisionInterface;
use App\Entity\User\Enums\UserRoles;
use App\Service\Application\RevisionNotificationInterface;
use Override;

/**
 * An activity waiting to be published is the board's to look at.
 */
final readonly class ActivityRevisionNotification implements RevisionNotificationInterface
{
    #[Override]
    public function supports(RevisionInterface $revision): bool
    {
        return $revision instanceof ActivityRevision;
    }

    #[Override]
    public function awaitingReviewType(RevisionInterface $revision): NotificationType
    {
        return NotificationType::ActivityAwaitingReview;
    }

    #[Override]
    public function audienceRole(RevisionInterface $revision): UserRoles
    {
        return UserRoles::Board;
    }
}
