<?php

declare(strict_types=1);

namespace App\Service\Activity;

use App\Entity\Activity\ActivityLocalisedText;
use App\Entity\Activity\ActivityRevision;
use App\Entity\Application\RevisionInterface;
use App\Workflow\RevisionClonerInterface;
use DateTime;
use Override;

use function assert;

/**
 * Spawns the next Draft {@see ActivityRevision} from an existing one (for "changes requested", reopening, or editing
 * an approved activity). The localised texts are deep-copied into fresh rows so orphan-removal can never delete the
 * source revision's content; the schedule, category and flags are copied by value.
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
}
