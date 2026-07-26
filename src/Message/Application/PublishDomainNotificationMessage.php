<?php

declare(strict_types=1);

namespace App\Message\Application;

use App\Entity\Application\Enums\NotificationType;

/**
 * Requests a notification for a domain event that just happened (an album or activity going public). Carries only the
 * type and the subject's key, which is all a notification ever records.
 */
class PublishDomainNotificationMessage
{
    public function __construct(
        private readonly NotificationType $type,
        private readonly int $subjectId,
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
}
