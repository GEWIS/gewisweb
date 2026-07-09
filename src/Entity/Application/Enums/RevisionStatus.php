<?php

declare(strict_types=1);

namespace App\Entity\Application\Enums;

use Override;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_map;

/**
 * The lifecycle state of a single revision in a revision chain.
 *
 * The string values are also the place names of the `revision` Symfony Workflow {@see config/packages/workflow.yaml}.
 * Keep the two in lockstep.
 *
 *   Draft -> Submitted -> InReview -> { ChangesRequested -> Draft(N+1) | Rejected -> (reopen | Closed) | Approved }
 */
enum RevisionStatus: string implements TranslatableInterface
{
    /** The author is still editing this revision; it is the only mutable state. The initial place. */
    case Draft = 'draft';

    /** The author handed the revision to the board; awaiting a reviewer to pick it up. */
    case Submitted = 'submitted';

    /** A reviewer is actively reviewing this revision. */
    case InReview = 'in-review';

    /** The board asked for changes; this revision becomes an immutable record and a new Draft (N+1) is spawned. */
    case ChangesRequested = 'changes-requested';

    /** The board rejected this revision. Terminal, but the chain can be reopened into a fresh Draft. */
    case Rejected = 'rejected';

    /** A rejected chain that was definitively closed. Hard terminal. */
    case Closed = 'closed';

    /** The board approved this revision. Immutable ("final"); the latest Approved revision is the live one. */
    case Approved = 'approved';

    /**
     * Whether a revision in this state is (potentially) the publicly visible, live version. The actual live revision
     * is the Approved one with the highest revision number; see {@see RevisableInterface::getLiveRevision()}.
     */
    public function isLive(): bool
    {
        return self::Approved === $this;
    }

    /**
     * Whether this state ends a revision's own lifecycle. A terminal revision is never mutated again; the chain
     * continues (if at all) through a newly spawned revision.
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::ChangesRequested,
            self::Rejected,
            self::Closed,
            self::Approved => true,
            default => false,
        };
    }

    /**
     * Whether the author may still edit this revision in place (rather than having to spawn a new one).
     */
    public function isEditableByAuthor(): bool
    {
        return self::Draft === $this;
    }

    /**
     * The states that make sense as a filter in the board's review queue.
     *
     * @return list<self>
     */
    public static function reviewableCases(): array
    {
        return [
            self::Submitted,
            self::InReview,
            self::ChangesRequested,
            self::Rejected,
            self::Approved,
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $case): string => $case->value,
            self::cases(),
        );
    }

    #[Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return match ($this) {
            self::Draft => $translator->trans(
                'Draft',
                locale: $locale,
            ),
            self::Submitted => $translator->trans(
                'Submitted',
                locale: $locale,
            ),
            self::InReview => $translator->trans(
                'In review',
                locale: $locale,
            ),
            self::ChangesRequested => $translator->trans(
                'Changes requested',
                locale: $locale,
            ),
            self::Rejected => $translator->trans(
                'Rejected',
                locale: $locale,
            ),
            self::Closed => $translator->trans(
                'Closed',
                locale: $locale,
            ),
            self::Approved => $translator->trans(
                'Approved',
                locale: $locale,
            ),
        };
    }
}
