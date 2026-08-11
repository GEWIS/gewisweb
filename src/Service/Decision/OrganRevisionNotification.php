<?php

declare(strict_types=1);

namespace App\Service\Decision;

use App\Entity\Application\Enums\NotificationType;
use App\Entity\Application\RevisionInterface;
use App\Entity\Decision\OrganInformationRevision;
use App\Entity\User\Enums\UserRoles;
use App\Service\Application\RevisionNotificationInterface;
use Override;

/**
 * What a body writes about itself is the board's to look at, and nobody else's.
 */
final readonly class OrganRevisionNotification implements RevisionNotificationInterface
{
    #[Override]
    public function supports(RevisionInterface $revision): bool
    {
        return $revision instanceof OrganInformationRevision;
    }

    #[Override]
    public function awaitingReviewType(RevisionInterface $revision): NotificationType
    {
        return NotificationType::OrganInformationRevisionAwaitingReview;
    }

    #[Override]
    public function audienceRole(RevisionInterface $revision): UserRoles
    {
        return UserRoles::Board;
    }
}
