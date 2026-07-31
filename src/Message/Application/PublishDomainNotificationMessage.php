<?php

declare(strict_types=1);

namespace App\Message\Application;

use App\Entity\Application\Enums\NotificationType;
use App\Entity\User\Enums\UserRoles;

/**
 * Requests a notification for a domain event that just happened (an album going public, an activity submitted for
 * review). Carries the type, the subject's key, and who it is for when that is a role rather than everybody.
 */
class PublishDomainNotificationMessage
{
    public function __construct(
        private readonly NotificationType $type,
        private readonly int $subjectId,
        private readonly ?UserRoles $recipientRole = null,
    ) {
    }

    public function getType(): NotificationType
    {
        return $this->type;
    }

    public function getSubjectId(): int
    {
        return $this->subjectId;
    }

    /**
     * The role this is for, or null when it is for everybody.
     */
    public function getRecipientRole(): ?UserRoles
    {
        return $this->recipientRole;
    }
}
