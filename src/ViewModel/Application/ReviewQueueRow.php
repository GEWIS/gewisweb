<?php

declare(strict_types=1);

namespace App\ViewModel\Application;

use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Application\RevisionInterface;

/**
 * One line of a review queue: what is waiting, who put it forward, which revision it is and where to go to look at it.
 * Every module's queue says the same four things, so they render through one partial rather than each writing out its
 * own table.
 */
final readonly class ReviewQueueRow
{
    public function __construct(
        public string $subject,
        public string $author,
        public int $revisionNumber,
        public RevisionStatus $status,
        public string $reviewRoute,
        public int $revisionId,
        // The revision the public is seeing while this one waits, so a queue does not read as if nothing is up.
        public ?int $liveRevisionNumber = null,
    ) {
    }

    /**
     * @param string $subject what the reader recognises this by, which the domain has to say: a localised activity
     *                        name, a company name, a vacancy slug
     */
    public static function fromRevision(
        RevisionInterface $revision,
        string $subject,
        string $reviewRoute,
    ): self {
        return new self(
            subject: $subject,
            author: $revision->getAuthorDisplayName(),
            revisionNumber: $revision->getRevisionNumber(),
            status: $revision->getStatus(),
            reviewRoute: $reviewRoute,
            revisionId: (int) $revision->getId(),
            liveRevisionNumber: $revision->getLiveCounterpart()?->getRevisionNumber(),
        );
    }

    /**
     * A whole queue at once. How a revision is named is still the domain's to say, so it hands that over as a
     * callback rather than the loop being written out again per module.
     *
     * @param iterable<RevisionInterface>        $revisions
     * @param callable(RevisionInterface):string $subject
     *
     * @return list<self>
     */
    public static function fromRevisions(
        iterable $revisions,
        callable $subject,
        string $reviewRoute,
    ): array {
        $rows = [];
        foreach ($revisions as $revision) {
            $rows[] = self::fromRevision(
                $revision,
                $subject($revision),
                $reviewRoute,
            );
        }

        return $rows;
    }
}
