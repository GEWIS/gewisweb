<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\Enums\NotificationType;
use App\Entity\Application\RevisionInterface;
use App\Entity\User\Enums\UserRoles;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Who a domain wants told that one of its revisions is waiting for a reviewer, and under which kind of notification.
 *
 * One per module rather than a branch in the listener that sends it, so a new revisable domain arrives with its own
 * answer instead of everybody editing the same match. The listener itself is
 * {@see \App\EventListener\Application\NotifyOnRevisionSubmissionListener}.
 */
#[AutoconfigureTag('app.revision_notification')]
interface RevisionNotificationInterface
{
    public function supports(RevisionInterface $revision): bool;

    /**
     * The kind of notification a submission of this domain's revisions raises.
     */
    public function awaitingReviewType(RevisionInterface $revision): NotificationType;

    /**
     * The role it is addressed to. A role rather than each member holding it, because who holds a role is worked out
     * from current installations rather than stored, and one row per submission beats one per reviewer either way.
     */
    public function audienceRole(RevisionInterface $revision): UserRoles;
}
