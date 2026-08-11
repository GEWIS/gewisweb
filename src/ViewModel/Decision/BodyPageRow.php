<?php

declare(strict_types=1);

namespace App\ViewModel\Decision;

use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Decision\Organ;
use App\Entity\Decision\OrganInformation;

/**
 * One line of the bodies overview: the body, whether its page has ever been written, what is happening to it now, and
 * whether anything of it is on the website.
 *
 * A body with no page at all is the ordinary starting state rather than an error, so everything about the page is
 * optional here and the screen says "not started" instead of hiding the row.
 */
final readonly class BodyPageRow
{
    public function __construct(
        public Organ $organ,
        public ?OrganInformation $page,
        public ?RevisionStatus $status,
        public ?int $revisionNumber,
        public ?int $currentRevisionId,
        public bool $published,
        // The revision visitors are seeing while the working one is not, so a page with a draft or a rejected revision
        // in hand is not read as one with nothing on the website. Null when the working revision is the live one.
        public ?int $liveRevisionNumber,
        // The working draft answers a review that asked for changes, so there is feedback waiting to be acted on.
        public bool $changesRequested,
    ) {
    }

    public static function fromOrgan(Organ $organ): self
    {
        $page = $organ->getOrganInformation();
        $current = $page?->getCurrentRevision();
        $previous = $current?->getPreviousRevision();

        return new self(
            organ: $organ,
            page: $page,
            status: $current?->getStatus(),
            revisionNumber: $current?->getRevisionNumber(),
            currentRevisionId: $current?->getId(),
            published: true === $page?->isPublished(),
            liveRevisionNumber: $current?->getLiveCounterpart()?->getRevisionNumber(),
            changesRequested: RevisionStatus::Draft === $current?->getStatus()
                && RevisionStatus::ChangesRequested === $previous?->getStatus(),
        );
    }

    /**
     * @param iterable<Organ> $organs
     *
     * @return list<self>
     */
    public static function fromOrgans(iterable $organs): array
    {
        $rows = [];

        foreach ($organs as $organ) {
            $rows[] = self::fromOrgan($organ);
        }

        return $rows;
    }
}
