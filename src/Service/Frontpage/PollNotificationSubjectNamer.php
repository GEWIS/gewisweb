<?php

declare(strict_types=1);

namespace App\Service\Frontpage;

use App\Entity\Application\Enums\NotificationType;
use App\Repository\Frontpage\PollRevisionRepository;
use App\Service\Application\AbstractNotificationSubjectNamer;
use Override;

/**
 * A poll awaiting review reads by the question it asks, which is the only thing about it the board recognises.
 */
final class PollNotificationSubjectNamer extends AbstractNotificationSubjectNamer
{
    public function __construct(private readonly PollRevisionRepository $revisionRepository)
    {
    }

    #[Override]
    public function supports(NotificationType $type): bool
    {
        return NotificationType::PollRevisionAwaitingReview === $type;
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

        foreach ($this->revisionRepository->findBy(['id' => $subjectIds]) as $revision) {
            $id = $revision->getId();
            if (null === $id) {
                continue;
            }

            $names[$id] = $this->localised($revision->getQuestion());
        }

        return $names;
    }
}
