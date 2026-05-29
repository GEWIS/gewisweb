<?php

declare(strict_types=1);

namespace App\Workflow;

use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Application\RevisionInterface;
use Override;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;

use function array_key_first;
use function assert;

/**
 * Bridges the `revision` state machine's single marking and a revision's {@see RevisionStatus} enum column.
 *
 * The workflow place names are exactly the enum's backing values, so the conversion is a direct
 * {@see RevisionStatus::from()} / `->value`. Using an explicit store (rather than the built-in `method` store) keeps
 * the enum/place mapping unambiguous regardless of the Workflow component version.
 */
final class RevisionStatusMarkingStore implements MarkingStoreInterface
{
    #[Override]
    public function getMarking(object $subject): Marking
    {
        assert($subject instanceof RevisionInterface);

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
        assert($subject instanceof RevisionInterface);

        $place = array_key_first($marking->getPlaces());
        assert(null !== $place);

        $subject->setStatus(RevisionStatus::from($place));
    }
}
