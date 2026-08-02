<?php

declare(strict_types=1);

namespace App\Service\Decision;

use App\Entity\Decision\Enums\MeetingActivityVerbs;
use App\Entity\Decision\Meeting;
use App\Entity\Decision\MeetingActivityLog;
use App\Entity\User\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Records one activity feed entry. Only persists; the calling service flushes together with the change itself.
 */
final readonly class MeetingActivityLogger
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function log(
        ?User $actor,
        ?Meeting $meeting,
        MeetingActivityVerbs $verb,
        string $subject,
    ): void {
        $entry = new MeetingActivityLog();
        $entry->setActor($actor);
        $entry->setMeeting($meeting);
        $entry->setVerb($verb);
        $entry->setSubject($subject);

        $this->entityManager->persist($entry);
    }
}
