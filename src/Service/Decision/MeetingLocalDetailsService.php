<?php

declare(strict_types=1);

namespace App\Service\Decision;

use App\Entity\Decision\Enums\MeetingActivityVerbs;
use App\Entity\Decision\Meeting;
use App\Entity\Decision\MeetingLocalDetails;
use App\Entity\User\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

use function preg_match;
use function trim;

/**
 * The locally-owned time and place of a meeting; everything else about a meeting is synced from GEWISDB.
 */
final readonly class MeetingLocalDetailsService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MeetingActivityLogger $activityLogger,
    ) {
    }

    /**
     * Upserts the details; an unparseable or empty start time and an empty location clear the respective field.
     */
    public function updateDetails(
        Meeting $meeting,
        ?string $startTime,
        ?string $location,
        User $actor,
    ): void {
        $details = $meeting->getLocalDetails();
        $isNew = null === $details;

        if (null === $details) {
            $details = new MeetingLocalDetails();
            $details->setMeeting($meeting);
            $this->entityManager->persist($details);
        }

        $time = null;
        if (
            null !== $startTime
            && 1 === preg_match(
                '/^\d{1,2}:\d{2}$/',
                trim($startTime),
            )
        ) {
            $time = new DateTime(trim($startTime));
        }

        $location = null === $location || '' === trim($location)
            ? null
            : trim($location);

        if (
            !$isNew
            && $time?->format('H:i') === $details->getStartTime()?->format('H:i')
            && $location === $details->getLocation()
        ) {
            return;
        }

        $details->setStartTime($time);
        $details->setLocation($location);

        $this->activityLogger->log(
            $actor,
            $meeting,
            MeetingActivityVerbs::DetailsUpdated,
            '',
        );
        $this->entityManager->flush();
    }
}
