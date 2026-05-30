<?php

declare(strict_types=1);

namespace App\Service\Activity;

use App\Entity\Activity\ActivityLocalisedText;
use App\Entity\Activity\ActivityRevision;
use App\Entity\Activity\SignupField;
use App\Entity\Activity\SignupList;
use App\Entity\Activity\SignupOption;
use App\Entity\Application\RevisionInterface;
use App\Workflow\RevisionClonerInterface;
use DateTime;
use Override;

use function assert;

/**
 * Spawns the next Draft {@see ActivityRevision} from an existing one (for "changes requested", reopening, or editing
 * an approved activity). The localised texts are deep-copied into fresh rows so orphan-removal can never delete the
 * source revision's content; the schedule, category and flags are copied by value. The sign-up lists (with their
 * fields and options) are deep-cloned too, carrying their lineage id forward but never their sign-ups; on approval the
 * sign-ups are migrated from the outgoing live revision's lists onto these clones.
 */
final readonly class ActivityRevisionCloner implements RevisionClonerInterface
{
    #[Override]
    public function supports(RevisionInterface $revision): bool
    {
        return $revision instanceof ActivityRevision;
    }

    #[Override]
    public function cloneAsDraft(RevisionInterface $source): ActivityRevision
    {
        assert($source instanceof ActivityRevision);

        $activity = $source->getActivity();

        $draft = new ActivityRevision();
        $draft->setAuthor($source->getAuthor());
        $draft->setAuthorCompanyUser($source->getAuthorCompanyUser());
        $draft->setRevisionNumber($source->getRevisionNumber() + 1);
        $draft->setPreviousRevision($source);
        $draft->setName($this->copyText($source->getName()));
        $draft->setLocation($this->copyText($source->getLocation()));
        $draft->setCosts($this->copyText($source->getCosts()));
        $draft->setDescription($this->copyText($source->getDescription()));
        $draft->setBeginTime($this->copyDate($source->getBeginTime()));
        $draft->setEndTime($this->copyDate($source->getEndTime()));
        $draft->setCategory($source->getCategory());
        $draft->setRequireGEFLITST($source->getRequireGEFLITST());
        $draft->setRequireZettle($source->getRequireZettle());

        foreach ($source->getSignupLists() as $list) {
            $draft->addSignupList($this->copySignupList($list));
        }

        $activity->addRevision($draft);
        $activity->setCurrentRevision($draft);

        return $draft;
    }

    private function copyText(ActivityLocalisedText $source): ActivityLocalisedText
    {
        return new ActivityLocalisedText(
            $source->getValueEN(),
            $source->getValueNL(),
        );
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
        $list->setName($this->copyText($source->getName()));
        $list->setOpenDate(clone $source->getOpenDate());
        $list->setCloseDate(clone $source->getCloseDate());
        $list->setOnlyGEWIS($source->getOnlyGEWIS());
        $list->setDisplaySubscribedNumber($source->getDisplaySubscribedNumber());
        $list->setLimitedCapacity($source->getLimitedCapacity());
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
        $field->setName($this->copyText($source->getName()));
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
        $option->setValue($this->copyText($source->getValue()));

        return $option;
    }
}
