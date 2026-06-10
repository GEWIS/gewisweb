<?php

declare(strict_types=1);

namespace App\Service\Activity;

use App\Entity\Activity\ActivityRevision;
use App\Entity\Activity\SignupList;
use RuntimeException;

use function array_search;
use function count;
use function sprintf;

/**
 * Moves the live sign-ups onto a newly-approved revision's sign-up lists, matched by lineage id: a sign-up's list and
 * each of its field values are re-pointed to the approved revision's clone, which (because a sign-up list is
 * structurally frozen) has an identical field/option layout, so the values map across by ordinal.
 *
 * Migration can only become impossible through a race (the live list gains its first sign-up after the draft already
 * restructured it) or request tampering. {@see isMigratable()} lets the approval flow detect that up front. As such,
 * the Approve/Submit action is withheld and a clear message shown.
 *
 * {@see migrate()} hard-fails as a last-resort backstop so sign-up data is never silently corrupted.
 */
final readonly class SignupListMigrator
{
    /**
     * Whether the incoming revision can inherit the outgoing (live) revision's sign-ups without loss.
     */
    public function isMigratable(
        ActivityRevision $outgoing,
        ActivityRevision $incoming,
    ): bool {
        return null === $this->firstBlocker(
            $outgoing,
            $incoming,
        );
    }

    /**
     * Re-point every sign-up from the outgoing revision's lists onto the incoming revision's lineage-matched lists.
     */
    public function migrate(
        ActivityRevision $outgoing,
        ActivityRevision $incoming,
    ): void {
        $byLineage = $this->lineageMap($incoming);
        $blocker = $this->firstBlocker(
            $outgoing,
            $incoming,
            $byLineage,
        );
        if (null !== $blocker) {
            throw new RuntimeException(sprintf(
                'Cannot approve revision #%d: %s.',
                $incoming->getId() ?? 0,
                $blocker,
            ));
        }

        foreach ($outgoing->getSignupLists() as $oldList) {
            if ($oldList->getSignUps()->isEmpty()) {
                continue;
            }

            $this->migrateList(
                $oldList,
                $byLineage[$oldList->getLineageId()->toRfc4122()],
            );
        }
    }

    /**
     * The reason the incoming revision cannot inherit the live sign-ups, or null if it can: every sign-up outgoing list
     * must have a lineage-matched clone with an identical field/option layout in the incoming revision.
     *
     * @param array<string, SignupList>|null $byLineage the incoming lineage map, built on demand when not supplied
     */
    private function firstBlocker(
        ActivityRevision $outgoing,
        ActivityRevision $incoming,
        ?array $byLineage = null,
    ): ?string {
        $byLineage ??= $this->lineageMap($incoming);
        foreach ($outgoing->getSignupLists() as $oldList) {
            if ($oldList->getSignUps()->isEmpty()) {
                continue;
            }

            $newList = $byLineage[$oldList->getLineageId()->toRfc4122()] ?? null;
            if (!$newList instanceof SignupList) {
                return 'a sign-up list with sign-ups was removed in this revision';
            }

            if (
                !$this->structureMatches(
                    $oldList,
                    $newList,
                )
            ) {
                return 'the fields of a sign-up list with sign-ups were changed';
            }
        }

        return null;
    }

    /**
     * @return array<string, SignupList>
     */
    private function lineageMap(ActivityRevision $revision): array
    {
        $map = [];
        foreach ($revision->getSignupLists() as $list) {
            $map[$list->getLineageId()->toRfc4122()] = $list;
        }

        return $map;
    }

    private function structureMatches(
        SignupList $oldList,
        SignupList $newList,
    ): bool {
        $oldFields = $oldList->getFields()->getValues();
        $newFields = $newList->getFields()->getValues();

        if (count($oldFields) !== count($newFields)) {
            return false;
        }

        foreach ($oldFields as $i => $oldField) {
            $newField = $newFields[$i];
            if (
                $oldField->getType() !== $newField->getType()
                || $oldField->getOptions()->count() !== $newField->getOptions()->count()
            ) {
                return false;
            }
        }

        return true;
    }

    private function migrateList(
        SignupList $oldList,
        SignupList $newList,
    ): void {
        $oldFields = $oldList->getFields()->getValues();
        $newFields = $newList->getFields()->getValues();

        foreach ($oldList->getSignUps() as $signup) {
            $signup->setSignupList($newList);

            foreach ($signup->getFieldValues() as $fieldValue) {
                $oldField = $fieldValue->getField();
                $fieldIndex = array_search(
                    $oldField,
                    $oldFields,
                    true,
                );
                if (false === $fieldIndex) {
                    continue;
                }

                $newField = $newFields[$fieldIndex];
                $fieldValue->setField($newField);

                $oldOption = $fieldValue->getOption();
                if (null === $oldOption) {
                    continue;
                }

                $optionIndex = array_search(
                    $oldOption,
                    $oldField->getOptions()->getValues(),
                    true,
                );
                if (false === $optionIndex) {
                    continue;
                }

                $fieldValue->setOption($newField->getOptions()->getValues()[$optionIndex]);
            }
        }
    }
}
