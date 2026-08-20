<?php

declare(strict_types=1);

namespace App\Workflow;

use App\Entity\Activity\ActivityProposal;
use App\Entity\Activity\Enums\ProposalStatus;
use Override;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;

use function array_key_first;
use function assert;

/**
 * Bridges the `activity_proposal` state machine's single marking and a proposal's {@see ProposalStatus} enum column.
 *
 * The place names are exactly the enum's backing values, so the conversion is a direct {@see ProposalStatus::from()}
 * and `->value`, the same arrangement {@see RevisionStatusMarkingStore} uses for revisions.
 */
final class ProposalStatusMarkingStore implements MarkingStoreInterface
{
    #[Override]
    public function getMarking(object $subject): Marking
    {
        assert($subject instanceof ActivityProposal);

        return new Marking([$subject->getStatus()->value => 1]);
    }

    /**
     * @param array<array-key, mixed> $context
     */
    #[Override]
    public function setMarking(
        object $subject,
        Marking $marking,
        array $context = [],
    ): void {
        assert($subject instanceof ActivityProposal);

        $place = array_key_first($marking->getPlaces());
        assert(null !== $place);

        $subject->setStatus(ProposalStatus::from($place));
    }
}
