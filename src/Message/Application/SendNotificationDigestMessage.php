<?php

declare(strict_types=1);

namespace App\Message\Application;

use App\Entity\Application\Enums\NotificationType;

/**
 * One member's notification digest email: their queued notifications as English content, each keeping the type and
 * subject the template needs to link to it. Dispatched to the bulk transport by the digest job so mailing never blocks
 * it. The email itself has no locale; its boilerplate is always English.
 *
 * @psalm-type DigestEntry = array{text: string, linkLabel: string, type: NotificationType, subjectId: int}
 */
final readonly class SendNotificationDigestMessage
{
    /**
     * @param list<DigestEntry> $entries
     */
    public function __construct(
        private string $email,
        private string $name,
        private array $entries,
    ) {
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return list<DigestEntry>
     */
    public function getEntries(): array
    {
        return $this->entries;
    }
}
