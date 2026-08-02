<?php

declare(strict_types=1);

namespace App\Service\Activity;

use App\Entity\Application\Enums\NotificationType;
use App\Repository\Activity\ActivityRepository;
use App\Repository\Activity\ActivityRevisionRepository;
use App\Service\Application\AbstractNotificationSubjectNamer;
use Override;

/**
 * An announced activity reads by the activity itself; one awaiting review reads by the revision, since that is what
 * the reviewer is pointed at.
 */
final class ActivityNotificationSubjectNamer extends AbstractNotificationSubjectNamer
{
    public function __construct(
        private readonly ActivityRepository $activityRepository,
        private readonly ActivityRevisionRepository $revisionRepository,
    ) {
    }

    #[Override]
    public function supports(NotificationType $type): bool
    {
        return NotificationType::ActivityPublished === $type
            || NotificationType::ActivityAwaitingReview === $type;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function namesFor(
        NotificationType $type,
        array $subjectIds,
    ): array {
        $names = [];

        if (NotificationType::ActivityPublished === $type) {
            foreach ($this->activityRepository->findBy(['id' => $subjectIds]) as $activity) {
                $id = $activity->getId();
                if (null === $id) {
                    continue;
                }

                $names[$id] = $this->localised($activity->getName());
            }

            return $names;
        }

        foreach ($this->revisionRepository->findBy(['id' => $subjectIds]) as $revision) {
            $id = $revision->getId();
            if (null === $id) {
                continue;
            }

            $names[$id] = $this->localised($revision->getName());
        }

        return $names;
    }
}
