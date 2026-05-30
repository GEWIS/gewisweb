<?php

declare(strict_types=1);

namespace App\EventListener\Activity;

use App\Entity\Activity\ActivityRevision;
use App\Entity\Activity\SignupList;
use RuntimeException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\EnteredEvent;

use function array_search;
use function count;
use function sprintf;

/**
 * When an activity revision is approved it becomes the publicly live version. Because each revision owns its own
 * (cloned) sign-up lists, the existing sign-ups (which live on the outgoing live revision's lists) must be moved onto
 * the newly-approved revision's matching lists (matched by lineage id) before that revision is promoted, so the public
 * page keeps showing them and no sign-up is ever lost.
 *
 * A list with sign-ups is structurally frozen, so its clone has an identical field/option layout and each sign-up's
 * field values can be re-pointed by ordinal. If a signed-up list was removed or restructured in the approved revision
 * (only reachable by a race or by request tampering, since the form blocks it), the migration throws so the surrounding
 * flush never happens and the revision stays in review rather than silently corrupting sign-up data.
 *
 * Another race condition may exist when migrating the sign-ups while people are actively signing up to the activity.
 *
 * Runs in-memory only; the controller flushes after `$workflow->apply()`. This is the sole promoter for activity
 * revisions and therefore {@see PromoteLiveRevisionListener} ignores them.
 */
#[AsEventListener(event: 'workflow.revision.entered.approved')]
final readonly class MigrateSignupsOnApprovalListener
{
    public function __invoke(EnteredEvent $event): void
    {
        $revision = $event->getSubject();
        if (!$revision instanceof ActivityRevision) {
            return;
        }

        $activity = $revision->getActivity();
        $outgoing = $activity->getLiveRevision();

        if (
            null !== $outgoing
            && $outgoing !== $revision
        ) {
            $this->migrate(
                $outgoing,
                $revision,
            );
        }

        $activity->markRevisionLive($revision);
    }

    /**
     * Move every sign-up from the outgoing revision's lists onto the incoming revision's lists, matched by lineage.
     */
    private function migrate(
        ActivityRevision $outgoing,
        ActivityRevision $incoming,
    ): void {
        $incomingByLineage = [];
        foreach ($incoming->getSignupLists() as $list) {
            $incomingByLineage[$list->getLineageId()->toRfc4122()] = $list;
        }

        foreach ($outgoing->getSignupLists() as $oldList) {
            if ($oldList->getSignUps()->isEmpty()) {
                continue;
            }

            $newList = $incomingByLineage[$oldList->getLineageId()->toRfc4122()] ?? null;
            if (!$newList instanceof SignupList) {
                throw new RuntimeException(sprintf(
                    'Cannot approve revision #%d: a sign-up list with sign-ups was removed in this revision.',
                    $incoming->getId() ?? 0,
                ));
            }

            $this->migrateList(
                $oldList,
                $newList,
                $incoming,
            );
        }
    }

    private function migrateList(
        SignupList $oldList,
        SignupList $newList,
        ActivityRevision $incoming,
    ): void {
        $oldFields = $oldList->getFields()->getValues();
        $newFields = $newList->getFields()->getValues();

        // The clone of a signed-up list is structurally identical (the structure is frozen); bail out loudly if not.
        if (count($oldFields) !== count($newFields)) {
            throw $this->structureChanged($incoming);
        }

        foreach ($oldFields as $i => $oldField) {
            $newField = $newFields[$i];
            if (
                $oldField->getType() !== $newField->getType()
                || $oldField->getOptions()->count() !== $newField->getOptions()->count()
            ) {
                throw $this->structureChanged($incoming);
            }
        }

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

    private function structureChanged(ActivityRevision $incoming): RuntimeException
    {
        return new RuntimeException(sprintf(
            'Cannot approve revision #%d: the fields of a sign-up list with sign-ups were changed.',
            $incoming->getId() ?? 0,
        ));
    }
}
