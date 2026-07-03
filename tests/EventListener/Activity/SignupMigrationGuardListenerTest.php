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
use App\EventListener\Activity\SignupMigrationGuardListener;
use App\Service\Activity\SignupListMigrator;
use App\Tests\Support\BuildsGuardEvents;
use Override;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Uid\Uuid;

use function implode;

/**
 * Turns {@see \App\EventListener\Activity\MigrateSignupsOnApprovalListener}'s last-resort hard-fail into a clean,
 * up-front block: approving (or submitting) a revision that dropped or restructured a sign-up list still carrying live
 * sign-ups must be withheld, because those sign-ups could not be carried across. The allow-paths (no live revision, the
 * revision is itself the live one, or the structure is still migratable) must stay open. The real migrator is used:
 * its migratability decision is exactly what this guard defers to.
 */
final class SignupMigrationGuardListenerTest extends TestCase
{
    use BuildsGuardEvents;

    private SignupMigrationGuardListener $listener;

    #[Override]
    protected function setUp(): void
    {
        $this->listener = new SignupMigrationGuardListener(new SignupListMigrator());
    }

    public function testBlocksApproveWhenLiveSignupsCannotBeCarriedOver(): void
    {
        $activity = new Activity();
        $live = $this->revisionOn(
            $activity,
            Uuid::v4(),
            withSignup: true,
        );
        $activity->setLiveRevision($live);
        // The in-flight revision is on a *different* lineage: the live list (with sign-ups) was effectively removed, so
        // its sign-ups have nowhere to migrate.
        $incoming = $this->revisionOn(
            $activity,
            Uuid::v4(),
            withSignup: false,
        );

        $event = $this->guardEvent($incoming);
        $this->listener->onApprove($event);

        self::assertTrue($event->isBlocked());
        self::assertStringContainsString(
            'cannot be carried over',
            implode(
                "\n",
                $this->blockerMessages($event),
            ),
        );
    }

    public function testAllowsApproveWhenTheStructureIsStillMigratable(): void
    {
        $activity = new Activity();
        $lineageId = Uuid::v4();
        $live = $this->revisionOn(
            $activity,
            $lineageId,
            withSignup: true,
        );
        $activity->setLiveRevision($live);
        // A faithful clone on the same lineage: the sign-ups map across by ordinal, so approval is safe.
        $incoming = $this->revisionOn(
            $activity,
            $lineageId,
            withSignup: false,
        );

        $event = $this->guardEvent($incoming);
        $this->listener->onApprove($event);

        self::assertFalse($event->isBlocked());
    }

    public function testAllowsWhenThereIsNoLiveRevision(): void
    {
        $activity = new Activity();
        $incoming = $this->revisionOn(
            $activity,
            Uuid::v4(),
            withSignup: false,
        );

        $event = $this->guardEvent($incoming);
        $this->listener->onApprove($event);

        self::assertFalse($event->isBlocked());
    }

    public function testAllowsWhenTheRevisionIsItselfTheLiveOne(): void
    {
        $activity = new Activity();
        $revision = $this->revisionOn(
            $activity,
            Uuid::v4(),
            withSignup: true,
        );
        $activity->setLiveRevision($revision);

        $event = $this->guardEvent($revision);
        $this->listener->onApprove($event);

        self::assertFalse($event->isBlocked());
    }

    public function testIgnoresNonActivityRevisions(): void
    {
        $event = $this->guardEvent(new stdClass());
        $this->listener->onApprove($event);

        self::assertFalse($event->isBlocked());
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

    private function text(string $value): ActivityLocalisedText
    {
        return new ActivityLocalisedText(
            $value,
            $value,
        );
    }
}
