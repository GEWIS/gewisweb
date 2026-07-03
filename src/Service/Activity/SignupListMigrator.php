<?php

declare(strict_types=1);

namespace App\Service\Activity;

use App\Entity\Activity\ActivityLocalisedText;
use App\Entity\Activity\ActivityRevision;
use App\Entity\Activity\SignupField;
use App\Entity\Activity\SignupList;
use RuntimeException;

use function count;
use function spl_object_id;
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

        // The clone copies the live list's fields and options verbatim and in order, and the form freezes the whole
        // `fields` collection once a list has sign-ups, so a legitimate approval always presents an identical layout.
        // Compare identity (type, labels, value range, option labels) AND order, not just counts, so that a
        // reorder or rename which only a race or request tampering could introduce makes the ordinal mapping in
        // migrateList() refuse, rather than silently re-point a sign-up's answer at the wrong field or option.
        foreach ($oldFields as $i => $oldField) {
            $newField = $newFields[$i];
            if (
                $oldField->getType() !== $newField->getType()
                || $oldField->getMinimumValue() !== $newField->getMinimumValue()
                || $oldField->getMaximumValue() !== $newField->getMaximumValue()
                || !$this->localisedTextMatches(
                    $oldField->getName(),
                    $newField->getName(),
                )
                || !$this->optionsMatch(
                    $oldField,
                    $newField,
                )
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether two fields carry the same options, in the same order and with the same labels.
     */
    private function optionsMatch(
        SignupField $oldField,
        SignupField $newField,
    ): bool {
        $oldOptions = $oldField->getOptions()->getValues();
        $newOptions = $newField->getOptions()->getValues();

        if (count($oldOptions) !== count($newOptions)) {
            return false;
        }

        foreach ($oldOptions as $i => $oldOption) {
            if (
                !$this->localisedTextMatches(
                    $oldOption->getValue(),
                    $newOptions[$i]->getValue(),
                )
            ) {
                return false;
            }
        }

        return true;
    }

    private function localisedTextMatches(
        ActivityLocalisedText $old,
        ActivityLocalisedText $new,
    ): bool {
        return $old->getValueNL() === $new->getValueNL()
            && $old->getValueEN() === $new->getValueEN();
    }

    private function migrateList(
        SignupList $oldList,
        SignupList $newList,
    ): void {
        $oldFields = $oldList->getFields()->getValues();
        $newFields = $newList->getFields()->getValues();

        // structureMatches() has already proven the layouts are identical and equally ordered, so a field/option maps
        // to its clone purely by ordinal. Index the old fields and their options once (by object id) instead of an
        // array_search per sign-up value, turning an O(signups * fields^2) scan into a single linear pass.
        $fieldIndex = [];
        $optionIndexByField = [];
        foreach ($oldFields as $i => $oldField) {
            $fieldIndex[spl_object_id($oldField)] = $i;
            $optionIndexByField[$i] = [];
            foreach ($oldField->getOptions()->getValues() as $j => $oldOption) {
                $optionIndexByField[$i][spl_object_id($oldOption)] = $j;
            }
        }

        foreach ($oldList->getSignUps() as $signup) {
            $signup->setSignupList($newList);

            foreach ($signup->getFieldValues() as $fieldValue) {
                $i = $fieldIndex[spl_object_id($fieldValue->getField())] ?? null;
                if (null === $i) {
                    continue;
                }

                $newField = $newFields[$i];
                $fieldValue->setField($newField);

                $oldOption = $fieldValue->getOption();
                if (null === $oldOption) {
                    continue;
                }

                $j = $optionIndexByField[$i][spl_object_id($oldOption)] ?? null;
                if (null === $j) {
                    continue;
                }

                $fieldValue->setOption($newField->getOptions()->getValues()[$j]);
            }
        }
    }
}
