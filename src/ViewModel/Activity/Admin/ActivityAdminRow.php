<?php

declare(strict_types=1);

namespace App\ViewModel\Activity\Admin;

use App\Entity\Activity\Activity;
use App\Entity\Application\Enums\RevisionStatus;
use DateTime;
use DateTimeImmutable;

use function assert;

/**
 * Read-model view of an {@see Activity} row for the admin overview. Pre-computes the working revision's columns
 * (organ, both localised names, company, submitter, status, schedule) so the Twig stays declarative.
 */
final readonly class ActivityAdminRow
{
    public function __construct(
        public int $id,
        public int $revisionId,
        public ?string $organAbbr,
        public ?string $nameNL,
        public ?string $nameEN,
        public ?string $companyName,
        public string $submitter,
        public RevisionStatus $status,
        public ?DateTimeImmutable $beginTime,
        public bool $isLive,
        // The working revision is a draft that addresses a "changes requested" review (its predecessor was rejected
        // with feedback). Is surfaced in the overview so the author knows there is feedback to act on.
        public bool $changesRequested,
        // The activity has already taken place; an approved, passed activity is immutable and can no longer be revised.
        public bool $passed,
        // The board has cancelled this activity: it stays public with a notice but all sign-up interaction is frozen.
        public bool $cancelled,
        // The board has unpublished this activity: it is removed from public view and sign-up interaction is frozen.
        public bool $unpublished,
    ) {
    }

    public static function fromActivity(Activity $activity): self
    {
        $id = $activity->getId();
        assert(null !== $id);

        // The admin overview queries inner-join the current revision, so it is always present here.
        $revision = $activity->getCurrentRevision();
        assert(null !== $revision);

        $revisionId = $revision->getId();
        assert(null !== $revisionId);

        $beginTime = $revision->getBeginTime();
        $endTime = $revision->getEndTime();

        return new self(
            id: $id,
            revisionId: $revisionId,
            // Organ/company now live on the revision; the overview shows the working revision's values (not the
            // activity proxy, which would resolve to the live revision and hide a pending organ/company change).
            organAbbr: $revision->getOrgan()?->getAbbr(),
            nameNL: $revision->getName()->getValueNL(),
            nameEN: $revision->getName()->getValueEN(),
            companyName: $revision->getCompany()?->getName(),
            submitter: $revision->getAuthorDisplayName(),
            status: $revision->getStatus(),
            beginTime: null === $beginTime
                ? null
                : DateTimeImmutable::createFromMutable($beginTime),
            isLive: null !== $activity->getLiveRevision(),
            changesRequested: RevisionStatus::Draft === $revision->getStatus()
                && RevisionStatus::ChangesRequested === $revision->getPreviousRevision()?->getStatus(),
            passed: null !== $endTime && $endTime < new DateTime(),
            cancelled: $activity->isCancelled(),
            unpublished: $activity->isUnpublished(),
        );
    }
}
