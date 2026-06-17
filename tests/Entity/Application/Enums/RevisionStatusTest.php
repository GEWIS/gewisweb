<?php

declare(strict_types=1);

namespace App\Tests\Entity\Application\Enums;

use App\Entity\Application\Enums\RevisionStatus;
use PHPUnit\Framework\TestCase;

use function in_array;

/**
 * These predicates drive the voter (what is editable), the live-revision promotion (what is live) and the review queue
 * (what is reviewable), so a miscategorised case ripples widely. Each test walks every case so a newly added status
 * cannot silently default to the wrong side of a predicate.
 */
final class RevisionStatusTest extends TestCase
{
    public function testOnlyApprovedIsLive(): void
    {
        foreach (RevisionStatus::cases() as $status) {
            self::assertSame(
                RevisionStatus::Approved === $status,
                $status->isLive(),
                $status->value . ' was categorised incorrectly by isLive()',
            );
        }
    }

    public function testTerminalStatesAreExactlyTheImmutableOutcomes(): void
    {
        $terminal = [
            RevisionStatus::ChangesRequested,
            RevisionStatus::Rejected,
            RevisionStatus::Closed,
            RevisionStatus::Approved,
        ];

        foreach (RevisionStatus::cases() as $status) {
            self::assertSame(
                in_array(
                    $status,
                    $terminal,
                    true,
                ),
                $status->isTerminal(),
                $status->value . ' was categorised incorrectly by isTerminal()',
            );
        }
    }

    public function testOnlyADraftIsEditableByItsAuthor(): void
    {
        foreach (RevisionStatus::cases() as $status) {
            self::assertSame(
                RevisionStatus::Draft === $status,
                $status->isEditableByAuthor(),
                $status->value . ' was categorised incorrectly by isEditableByAuthor()',
            );
        }
    }

    public function testTheReviewQueueExcludesDraftsAndClosedChains(): void
    {
        self::assertSame(
            [
                RevisionStatus::Submitted,
                RevisionStatus::InReview,
                RevisionStatus::ChangesRequested,
                RevisionStatus::Rejected,
                RevisionStatus::Approved,
            ],
            RevisionStatus::reviewableCases(),
        );
    }

    public function testValuesMirrorTheWorkflowPlaceNamesInOrder(): void
    {
        // These string values are the place names in config/packages/workflow.yaml and must stay in lockstep with it.
        self::assertSame(
            [
                'draft',
                'submitted',
                'in-review',
                'changes-requested',
                'rejected',
                'closed',
                'approved',
            ],
            RevisionStatus::values(),
        );
    }
}
