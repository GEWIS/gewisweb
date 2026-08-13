<?php

declare(strict_types=1);

namespace App\Service\Decision;

use App\Entity\Application\Enums\NotificationType;
use App\Repository\Decision\OrganInformationRevisionRepository;
use App\Service\Application\AbstractNotificationSubjectNamer;
use Override;

/**
 * A page awaiting review reads by the body that wrote it, which is what the board recognises it as; the page itself has
 * no name of its own.
 */
final class OrganNotificationSubjectNamer extends AbstractNotificationSubjectNamer
{
    public function __construct(private readonly OrganInformationRevisionRepository $revisionRepository)
    {
    }

    #[Override]
    public function supports(NotificationType $type): bool
    {
        return NotificationType::OrganInformationRevisionAwaitingReview === $type;
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

            $abbr = $revision->getOrgan()->getAbbr();
            $names[$id] = [
                'en' => $abbr,
                'nl' => $abbr,
            ];
        }

        return $names;
    }
}
