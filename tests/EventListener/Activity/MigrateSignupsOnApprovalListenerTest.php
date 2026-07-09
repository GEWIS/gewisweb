<?php

declare(strict_types=1);

namespace App\Tests\EventListener\Activity;

use App\Entity\Activity\Activity;
use App\Entity\Activity\ActivityLocalisedText;
use App\Entity\Activity\ActivityRevision;
use App\Entity\Activity\Enums\SignupFieldTypes;
use App\Entity\Activity\ExternalSignup;
use App\Entity\Activity\SignupField;
use App\Entity\Activity\SignupFieldValue;
use App\Entity\Activity\SignupList;
use App\Entity\Activity\SignupOption;
use App\Entity\Application\RevisionInterface;
use App\EventListener\Activity\MigrateSignupsOnApprovalListener;
use App\Service\Activity\SignupListMigrator;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Marking;

/**
 * Approving an activity revision must migrate the live sign-ups onto the newly-approved revision's lineage-matched
 * lists, so the public page keeps showing them and no sign-up is lost. This listener does the migration only; the
 * promotion to live is the generic {@see \App\EventListener\Application\PromoteLiveRevisionListener}'s job, at a lower
 * priority so it repoints the live revision only after this listener has read the still-current one. With nothing
 * approved yet there is nothing to migrate, so it is a no-op. Non-activity revisions are ignored here. The real
 * migrator is used: this listener is the integration point that drives the migration.
 */
final class MigrateSignupsOnApprovalListenerTest extends TestCase
{
    private MigrateSignupsOnApprovalListener $listener;

    #[Override]
    protected function setUp(): void
    {
        $this->listener = new MigrateSignupsOnApprovalListener(new SignupListMigrator());
    }

    public function testDoesNothingWhenNothingIsLiveYet(): void
    {
        $activity = new Activity();
        $approved = $this->revisionOn(
            $activity,
            Uuid::v4(),
            withSignup: false,
        );

        $this->listener->__invoke($this->enteredEvent($approved));

        // There is nothing to migrate; promotion is the generic listener's job, so this one leaves the activity unlive.
        self::assertNull($activity->getLiveRevision());
    }

    public function testMigratesTheLiveSignupsOntoTheApprovedRevision(): void
    {
        $activity = new Activity();
        $lineageId = Uuid::v4();
        $live = $this->revisionOn(
            $activity,
            $lineageId,
            withSignup: true,
        );
        $activity->setLiveRevision($live);
        $approved = $this->revisionOn(
            $activity,
            $lineageId,
            withSignup: false,
        );

        $liveSignup = $live->getSignupLists()->getValues()[0]->getSignUps()->getValues()[0];

        $this->listener->__invoke($this->enteredEvent($approved));

        // The sign-up has been re-pointed onto the approved revision's lineage-matched list clone. Promotion is left to
        // the generic listener, so the live revision is still the outgoing one at this point.
        self::assertSame(
            $approved->getSignupLists()->getValues()[0],
            $liveSignup->getSignupList(),
        );
        self::assertSame(
            $live,
            $activity->getLiveRevision(),
        );
    }

    public function testIgnoresNonActivityRevisions(): void
    {
        $this->listener->__invoke($this->enteredEvent(self::createStub(RevisionInterface::class)));

        $this->expectNotToPerformAssertions();
    }

    /**
     * A revision attached to the activity owning a single choice-field sign-up list on the given lineage, optionally
     * already carrying one sign-up that answered that field's first option.
     */
    private function revisionOn(
        Activity $activity,
        Uuid $lineageId,
        bool $withSignup,
    ): ActivityRevision {
        $revision = new ActivityRevision();
        $activity->addRevision($revision);

        $list = new SignupList();
        $list->setLineageId($lineageId);

        $field = new SignupField();
        $field->setName($this->text('Colour'));
        $field->setType(SignupFieldTypes::Choice);
        $option = new SignupOption();
        $option->setValue($this->text('Red'));
        $field->addOption($option);
        $list->addField($field);
        $revision->addSignupList($list);

        if ($withSignup) {
            $signup = new ExternalSignup();
            $signup->setSignupList($list);
            $list->getSignUps()->add($signup);

            $fieldValue = new SignupFieldValue();
            $fieldValue->setField($field);
            $fieldValue->setSignup($signup);
            $fieldValue->setOption($option);
            $signup->getFieldValues()->add($fieldValue);
        }

        return $revision;
    }

    private function enteredEvent(object $subject): EnteredEvent
    {
        return new EnteredEvent(
            $subject,
            new Marking([]),
        );
    }

    private function text(string $value): ActivityLocalisedText
    {
        return new ActivityLocalisedText(
            $value,
            $value,
        );
    }
}
