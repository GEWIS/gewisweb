<?php

declare(strict_types=1);

namespace App\Service\Activity;

use App\Entity\Activity\ActivityRevision;
use App\Entity\Activity\SignupField;
use App\Entity\Activity\SignupList;
use App\Entity\Activity\SignupOption;
use App\Entity\Application\AbstractRevision;
use App\Entity\Application\RevisionInterface;
use App\Workflow\AbstractRevisionCloner;
use DateTime;
use Override;

use function assert;

/**
 * Spawns the next Draft {@see ActivityRevision} from an existing one (for "changes requested", reopening, or editing
 * an approved activity). The localised texts are deep-copied into fresh rows so orphan-removal can never delete the
 * source revision's content; the schedule, category and flags are copied by value, and the organ, company and labels
 * (reference entities) are carried over by reference. The sign-up lists (with their fields and options) are
 * deep-cloned too, carrying their lineage id forward but never their sign-ups; on approval the sign-ups are migrated
 * from the outgoing live revision's lists onto these clones. The shared workflow wiring lives in
 * {@see AbstractRevisionCloner}.
 */
final readonly class ActivityRevisionCloner extends AbstractRevisionCloner
{
    #[Override]
    public function supports(RevisionInterface $revision): bool
    {
        return $revision instanceof ActivityRevision;
    }

    #[Override]
    protected function spawnDraft(RevisionInterface $source): ActivityRevision
    {
        assert($source instanceof ActivityRevision);

        $activity = $source->getActivity();

        $draft = new ActivityRevision();
        $draft->setPreviousRevision($source);
        $activity->addRevision($draft);
        $activity->setCurrentRevision($draft);

        return $draft;
    }

    #[Override]
    protected function copyContent(
        RevisionInterface $source,
        AbstractRevision $draft,
    ): void {
        assert($source instanceof ActivityRevision);
        assert($draft instanceof ActivityRevision);

        $draft->setName($source->getName()->copy());
        $draft->setLocation($source->getLocation()->copy());
        $draft->setCosts($source->getCosts()->copy());
        $draft->setDescription($source->getDescription()->copy());
        $draft->setBeginTime($this->copyDate($source->getBeginTime()));
        $draft->setEndTime($this->copyDate($source->getEndTime()));
        $draft->setCategory($source->getCategory());
        $draft->setRequireGEFLITST($source->getRequireGEFLITST());
        $draft->setRequireZettle($source->getRequireZettle());
        // Organ and company are reference entities, copied by reference; the labels (also references) are re-assigned
        // to the draft. Without this the draft would lose the organiser and labels carried by the source revision.
        $draft->setOrgan($source->getOrgan());
        $draft->setCompany($source->getCompany());
        $draft->addLabels($source->getLabels()->toArray());

        foreach ($source->getSignupLists() as $list) {
            $draft->addSignupList($this->copySignupList($list));
        }
    }

    private function copyDate(?DateTime $source): ?DateTime
    {
        return null !== $source
            ? clone $source
            : null;
    }

    /**
     * Deep-clone a sign-up list onto the new draft: fresh name/date/flag values, the same lineage id, and deep-cloned
     * fields/options. Sign-ups are deliberately not copied (they stay on the live revision until approval migration).
     */
    private function copySignupList(SignupList $source): SignupList
    {
        $list = new SignupList();
        $list->setName($source->getName()->copy());
        $list->setOpenDate(clone $source->getOpenDate());
        $list->setCloseDate(clone $source->getCloseDate());
        $list->setOnlyGEWIS($source->getOnlyGEWIS());
        $list->setDisplaySubscribedNumber($source->getDisplaySubscribedNumber());
        $list->setLimitedCapacity($source->getLimitedCapacity());
        $list->setCapacity($source->getCapacity());
        // The draw lock + its audit carry across revisions, like presence does, so an approved re-edit keeps a list
        // that was already drawn locked.
        $list->setDrawnAt($source->getDrawnAt());
        $list->setDrawnBy($source->getDrawnBy());
        // Allocation method + its per-method settings are list config, carried forward like the other settings.
        $list->setAllocationMethod($source->getAllocationMethod());
        $list->setDrawCutoffRule($source->getDrawCutoffRule());
        $cutoffAt = $source->getDrawCutoffAt();
        $list->setDrawCutoffAt(null !== $cutoffAt ? clone $cutoffAt : null);
        $list->setDrawAfterDurationHours($source->getDrawAfterDurationHours());
        $list->setExternalPolicyUrl($source->getExternalPolicyUrl());
        $list->setExternalForceOrdering($source->getExternalForceOrdering());
        $list->setExternalPaymentByExternal($source->getExternalPaymentByExternal());
        $list->setCustomMethodDescription($source->getCustomMethodDescription());
        $list->setPresenceTaken($source->isPresenceTaken());
        $list->setPromoted($source->isPromoted());
        // Carry the lineage forward so approval can migrate the live sign-ups onto this clone.
        $list->setLineageId($source->getLineageId());

        foreach ($source->getFields() as $field) {
            $list->addField($this->copySignupField($field));
        }

        return $list;
    }

    private function copySignupField(SignupField $source): SignupField
    {
        $field = new SignupField();
        $field->setName($source->getName()->copy());
        $field->setType($source->getType());
        $field->setIsSensitive($source->isSensitive());
        $field->setMinimumValue($source->getMinimumValue());
        $field->setMaximumValue($source->getMaximumValue());

        foreach ($source->getOptions() as $option) {
            $field->addOption($this->copySignupOption($option));
        }

        return $field;
    }

    private function copySignupOption(SignupOption $source): SignupOption
    {
        $option = new SignupOption();
        $option->setValue($source->getValue()->copy());

        return $option;
    }
}
