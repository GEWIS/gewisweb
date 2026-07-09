<?php

declare(strict_types=1);

namespace App\Tests\Service\Activity;

use App\Entity\Activity\ActivityLocalisedText;
use App\Entity\Activity\ActivityRevision;
use App\Entity\Activity\Enums\SignupFieldTypes;
use App\Entity\Activity\ExternalSignup;
use App\Entity\Activity\SignupField;
use App\Entity\Activity\SignupFieldValue;
use App\Entity\Activity\SignupList;
use App\Entity\Activity\SignupOption;
use App\Entity\Decision\Member;
use App\Service\Activity\SignupListMigrator;
use DateTime;
use Override;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Uid\Uuid;

/**
 * The migrator re-points live sign-ups onto an approved revision's lineage-matched list clone purely by ordinal, which
 * is only safe while the layouts are provably identical. These tests pin that: a faithful clone migrates, and any
 * divergence a race or request tampering could introduce past the structural freeze (a reordered or renamed
 * field/option, a changed type or bound, an added/removed field, option or whole list) hard-fails rather than silently
 * corrupting an answer. Lists carrying no sign-ups have nothing to lose, so they are exempt from the structural check.
 */
final class SignupListMigratorTest extends TestCase
{
    private SignupListMigrator $migrator;

    #[Override]
    protected function setUp(): void
    {
        $this->migrator = new SignupListMigrator();
    }

    public function testMigratesAnswersOntoTheLineageMatchedCloneByOrdinal(): void
    {
        $lineageId = Uuid::v4();

        $live = $this->choiceFieldList(
            $lineageId,
            'Colour',
            [
                'Red',
                'Blue',
            ],
        );
        $outgoing = $this->revisionWith($live);
        [
            $signup, $fieldValue
        ] = $this->answerFirstOption($live);

        $clone = $this->choiceFieldList(
            $lineageId,
            'Colour',
            [
                'Red',
                'Blue',
            ],
        );
        $incoming = $this->revisionWith($clone);

        self::assertTrue($this->migrator->isMigratable(
            $outgoing,
            $incoming,
        ));

        $this->migrator->migrate(
            $outgoing,
            $incoming,
        );

        $clonedField = $clone->getFields()->getValues()[0];
        self::assertSame(
            $clone,
            $signup->getSignupList(),
        );
        self::assertSame(
            $clonedField,
            $fieldValue->getField(),
        );
        $migratedOption = $fieldValue->getOption();
        self::assertNotNull($migratedOption);
        self::assertSame(
            $clonedField->getOptions()->getValues()[0],
            $migratedOption,
        );
        self::assertSame(
            'Red',
            $migratedOption->getValue()->getValueEN(),
        );
    }

    public function testMigratesNonChoiceAnswersByRepointingTheFieldOnly(): void
    {
        $lineageId = Uuid::v4();

        $live = $this->listWith(
            $lineageId,
            $this->field(
                SignupFieldTypes::Text,
                'Name',
            ),
            $this->field(
                SignupFieldTypes::Number,
                'Guests',
                0,
                5,
            ),
            $this->field(
                SignupFieldTypes::YesNo,
                'Dietary',
            ),
        );
        $outgoing = $this->revisionWith($live);
        [
            $signup, $fieldValues
        ] = $this->answerScalars(
            $live,
            [
                'Alice',
                '3',
                'yes',
            ],
        );

        $clone = $this->listWith(
            $lineageId,
            $this->field(
                SignupFieldTypes::Text,
                'Name',
            ),
            $this->field(
                SignupFieldTypes::Number,
                'Guests',
                0,
                5,
            ),
            $this->field(
                SignupFieldTypes::YesNo,
                'Dietary',
            ),
        );
        $incoming = $this->revisionWith($clone);

        self::assertTrue($this->migrator->isMigratable(
            $outgoing,
            $incoming,
        ));

        $this->migrator->migrate(
            $outgoing,
            $incoming,
        );

        // Text/Number/Yes-No answers carry a raw value and no option, so migration only re-points the field to the
        // clone's same-ordinal field and leaves the value (and the null option) untouched.
        self::assertSame(
            $clone,
            $signup->getSignupList(),
        );
        $clonedFields = $clone->getFields()->getValues();
        foreach ($fieldValues as $ordinal => $fieldValue) {
            self::assertSame(
                $clonedFields[$ordinal],
                $fieldValue->getField(),
            );
            self::assertNull($fieldValue->getOption());
        }

        self::assertSame(
            'Alice',
            $fieldValues[0]->getValue(),
        );
        self::assertSame(
            '3',
            $fieldValues[1]->getValue(),
        );
    }

    public function testRefusesToMigrateWhenOptionsWereReordered(): void
    {
        $lineageId = Uuid::v4();

        $live = $this->choiceFieldList(
            $lineageId,
            'Colour',
            [
                'Red',
                'Blue',
            ],
        );
        $outgoing = $this->revisionWith($live);
        $this->answerFirstOption($live);

        // Same field/option count and type, but the options are in the opposite order: ordinal mapping would silently
        // re-point the "Red" answer onto the clone's "Blue", so the migrator must refuse rather than corrupt it.
        $clone = $this->choiceFieldList(
            $lineageId,
            'Colour',
            [
                'Blue',
                'Red',
            ],
        );
        $incoming = $this->revisionWith($clone);

        self::assertFalse($this->migrator->isMigratable(
            $outgoing,
            $incoming,
        ));

        $this->expectException(RuntimeException::class);
        $this->migrator->migrate(
            $outgoing,
            $incoming,
        );
    }

    public function testRefusesToMigrateWhenAFieldWasRenamed(): void
    {
        $lineageId = Uuid::v4();

        $live = $this->choiceFieldList(
            $lineageId,
            'Colour',
            [
                'Red',
                'Blue',
            ],
        );
        $outgoing = $this->revisionWith($live);
        $this->answerFirstOption($live);

        $clone = $this->choiceFieldList(
            $lineageId,
            'Favourite colour',
            [
                'Red',
                'Blue',
            ],
        );
        $incoming = $this->revisionWith($clone);

        self::assertFalse($this->migrator->isMigratable(
            $outgoing,
            $incoming,
        ));
    }

    public function testRefusesToMigrateWhenAListWithSignupsWasRemoved(): void
    {
        $live = $this->choiceFieldList(
            Uuid::v4(),
            'Colour',
            [
                'Red',
                'Blue',
            ],
        );
        $outgoing = $this->revisionWith($live);
        $this->answerFirstOption($live);

        // The incoming revision carries a list on a *different* lineage, which for migration purposes is no match at
        // all: the live sign-ups would have nowhere to go, so the migrator refuses rather than orphan them.
        $incoming = $this->revisionWith($this->choiceFieldList(
            Uuid::v4(),
            'Colour',
            [
                'Red',
                'Blue',
            ],
        ));

        self::assertFalse($this->migrator->isMigratable(
            $outgoing,
            $incoming,
        ));

        $this->expectException(RuntimeException::class);
        $this->migrator->migrate(
            $outgoing,
            $incoming,
        );
    }

    public function testRefusesToMigrateWhenTheFieldCountDiffers(): void
    {
        $lineageId = Uuid::v4();

        $live = $this->listWith(
            $lineageId,
            $this->field(
                SignupFieldTypes::Text,
                'Name',
            ),
        );
        $outgoing = $this->revisionWith($live);
        $this->answerScalars(
            $live,
            ['Alice'],
        );

        // A frozen list never gains a field; an extra one can only come from a race or tampering, and the ordinal
        // mapping would have no counterpart for it, so the layouts are not provably identical.
        $incoming = $this->revisionWith($this->listWith(
            $lineageId,
            $this->field(
                SignupFieldTypes::Text,
                'Name',
            ),
            $this->field(
                SignupFieldTypes::Text,
                'Nickname',
            ),
        ));

        self::assertFalse($this->migrator->isMigratable(
            $outgoing,
            $incoming,
        ));
    }

    public function testRefusesToMigrateWhenAFieldTypeChanged(): void
    {
        $lineageId = Uuid::v4();

        $live = $this->listWith(
            $lineageId,
            $this->field(
                SignupFieldTypes::Text,
                'Age',
            ),
        );
        $outgoing = $this->revisionWith($live);
        $this->answerScalars(
            $live,
            ['21'],
        );

        // Same name and (absent) bounds, but a Text answer is not a Number answer; re-pointing it would silently
        // reinterpret the stored value's type, so the layouts do not match.
        $incoming = $this->revisionWith($this->listWith(
            $lineageId,
            $this->field(
                SignupFieldTypes::Number,
                'Age',
            ),
        ));

        self::assertFalse($this->migrator->isMigratable(
            $outgoing,
            $incoming,
        ));
    }

    public function testRefusesToMigrateWhenANumberFieldsBoundsChanged(): void
    {
        $lineageId = Uuid::v4();

        $live = $this->listWith(
            $lineageId,
            $this->field(
                SignupFieldTypes::Number,
                'Guests',
                0,
                2,
            ),
        );
        $outgoing = $this->revisionWith($live);
        $this->answerScalars(
            $live,
            ['2'],
        );

        // The stored "2" was valid under max 2; widening the clone's bound is a structural change a frozen list should
        // never present, so the migrator refuses rather than re-home an answer under different rules.
        $incoming = $this->revisionWith($this->listWith(
            $lineageId,
            $this->field(
                SignupFieldTypes::Number,
                'Guests',
                0,
                5,
            ),
        ));

        self::assertFalse($this->migrator->isMigratable(
            $outgoing,
            $incoming,
        ));
    }

    public function testRefusesToMigrateWhenAnOptionWasAdded(): void
    {
        $lineageId = Uuid::v4();

        $live = $this->choiceFieldList(
            $lineageId,
            'Colour',
            [
                'Red',
                'Blue',
            ],
        );
        $outgoing = $this->revisionWith($live);
        $this->answerFirstOption($live);

        // The added option shifts no existing ordinal, but the option counts differ, which is enough to prove the
        // layouts are not the verbatim clone the ordinal mapping relies on.
        $incoming = $this->revisionWith($this->choiceFieldList(
            $lineageId,
            'Colour',
            [
                'Red',
                'Blue',
                'Green',
            ],
        ));

        self::assertFalse($this->migrator->isMigratable(
            $outgoing,
            $incoming,
        ));
    }

    public function testBlocksTheWholeApprovalWhenOnlyOneOfSeveralListsDiverged(): void
    {
        $faithfulLineage = Uuid::v4();
        $divergedLineage = Uuid::v4();

        $liveFaithful = $this->choiceFieldList(
            $faithfulLineage,
            'Colour',
            [
                'Red',
                'Blue',
            ],
        );
        $liveDiverged = $this->choiceFieldList(
            $divergedLineage,
            'Size',
            [
                'S',
                'M',
            ],
        );
        $outgoing = $this->revisionWithLists(
            $liveFaithful,
            $liveDiverged,
        );
        [
            $faithfulSignup,
        ] = $this->answerFirstOption($liveFaithful);
        $this->answerFirstOption($liveDiverged);

        // One list clones faithfully, the other was renamed. Migration is all-or-nothing: it must refuse before
        // touching anything, leaving even the faithful list's sign-up on the outgoing revision rather than half-done.
        $incoming = $this->revisionWithLists(
            $this->choiceFieldList(
                $faithfulLineage,
                'Colour',
                [
                    'Red',
                    'Blue',
                ],
            ),
            $this->choiceFieldList(
                $divergedLineage,
                'Dimensions',
                [
                    'S',
                    'M',
                ],
            ),
        );

        self::assertFalse($this->migrator->isMigratable(
            $outgoing,
            $incoming,
        ));

        try {
            $this->migrator->migrate(
                $outgoing,
                $incoming,
            );
            self::fail('Expected migrate() to refuse a partially-diverged revision.');
        } catch (RuntimeException) {
        }

        self::assertSame(
            $liveFaithful,
            $faithfulSignup->getSignupList(),
        );
    }

    public function testCarriesDrawAndPresenceStateFromTheLiveListOntoTheClone(): void
    {
        $lineageId = Uuid::v4();

        // The live list was drawn (and had presence taken) after the draft was cloned, so the clone's snapshot is
        // stale; migration must re-sync the live state onto the clone.
        $live = $this->choiceFieldList(
            $lineageId,
            'Colour',
            [
                'Red',
                'Blue',
            ],
        );
        $drawnAt = new DateTime('2026-01-02 03:04:05');
        $drawnBy = self::createStub(Member::class);
        $live->setDrawnAt($drawnAt);
        $live->setDrawnBy($drawnBy);
        $live->setPresenceTaken(true);
        $outgoing = $this->revisionWith($live);
        $this->answerFirstOption($live);

        $clone = $this->choiceFieldList(
            $lineageId,
            'Colour',
            [
                'Red',
                'Blue',
            ],
        );
        // The clone starts from a pre-draw snapshot.
        $clone->setDrawnAt(null);
        $clone->setDrawnBy(null);
        $clone->setPresenceTaken(false);
        $incoming = $this->revisionWith($clone);

        $this->migrator->migrate(
            $outgoing,
            $incoming,
        );

        self::assertSame(
            $drawnAt,
            $clone->getDrawnAt(),
        );
        self::assertSame(
            $drawnBy,
            $clone->getDrawnBy(),
        );
        self::assertTrue($clone->isPresenceTaken());
    }

    public function testCarriesDrawAndPresenceStateEvenForListsWithoutSignups(): void
    {
        $lineageId = Uuid::v4();

        // A drawn but empty list (a limited-capacity list nobody signed up for) is skipped by the sign-up move, but its
        // draw state is still authoritative on the live list and must reach the clone.
        $live = $this->choiceFieldList(
            $lineageId,
            'Colour',
            [
                'Red',
                'Blue',
            ],
        );
        $drawnAt = new DateTime('2026-02-03 04:05:06');
        $live->setDrawnAt($drawnAt);
        $live->setPresenceTaken(true);
        $outgoing = $this->revisionWith($live);

        $clone = $this->choiceFieldList(
            $lineageId,
            'Colour',
            [
                'Red',
                'Blue',
            ],
        );
        $clone->setDrawnAt(null);
        $clone->setPresenceTaken(false);
        $incoming = $this->revisionWith($clone);

        $this->migrator->migrate(
            $outgoing,
            $incoming,
        );

        self::assertSame(
            $drawnAt,
            $clone->getDrawnAt(),
        );
        self::assertTrue($clone->isPresenceTaken());
    }

    public function testIgnoresListsWithoutSignupsWhenJudgingMigratability(): void
    {
        // A list carrying no sign-ups has nothing to carry over, so its fate is irrelevant: even dropping it outright
        // (the incoming revision has no matching lineage) leaves the migration trivially safe and a no-op.
        $outgoing = $this->revisionWith($this->choiceFieldList(
            Uuid::v4(),
            'Colour',
            [
                'Red',
                'Blue',
            ],
        ));
        $incoming = new ActivityRevision();

        self::assertTrue($this->migrator->isMigratable(
            $outgoing,
            $incoming,
        ));

        $this->migrator->migrate(
            $outgoing,
            $incoming,
        );
    }

    private function revisionWith(SignupList $list): ActivityRevision
    {
        return $this->revisionWithLists($list);
    }

    private function revisionWithLists(SignupList ...$lists): ActivityRevision
    {
        $revision = new ActivityRevision();
        foreach ($lists as $list) {
            $revision->addSignupList($list);
        }

        return $revision;
    }

    /**
     * A sign-up list on the given lineage with a single choice field carrying the given (ordered) option labels.
     *
     * @param string[] $optionLabels
     */
    private function choiceFieldList(
        Uuid $lineageId,
        string $fieldName,
        array $optionLabels,
    ): SignupList {
        return $this->listWith(
            $lineageId,
            $this->field(
                SignupFieldTypes::Choice,
                $fieldName,
                optionLabels: $optionLabels,
            ),
        );
    }

    private function listWith(
        Uuid $lineageId,
        SignupField ...$fields,
    ): SignupList {
        $list = new SignupList();
        $list->setLineageId($lineageId);

        foreach ($fields as $field) {
            $list->addField($field);
        }

        return $list;
    }

    /**
     * @param string[] $optionLabels
     */
    private function field(
        SignupFieldTypes $type,
        string $name,
        ?int $minimumValue = null,
        ?int $maximumValue = null,
        array $optionLabels = [],
    ): SignupField {
        $field = new SignupField();
        $field->setName($this->text($name));
        $field->setType($type);
        $field->setMinimumValue($minimumValue);
        $field->setMaximumValue($maximumValue);

        foreach ($optionLabels as $label) {
            $option = new SignupOption();
            $option->setValue($this->text($label));
            $field->addOption($option);
        }

        return $field;
    }

    /**
     * Add one external sign-up to the list that answered its first field with that field's first option.
     *
     * @return array{0: ExternalSignup, 1: SignupFieldValue}
     */
    private function answerFirstOption(SignupList $list): array
    {
        $field = $list->getFields()->getValues()[0];
        $option = $field->getOptions()->getValues()[0];

        $signup = new ExternalSignup();
        $signup->setSignupList($list);
        $list->getSignUps()->add($signup);

        $fieldValue = new SignupFieldValue();
        $fieldValue->setField($field);
        $fieldValue->setSignup($signup);
        $fieldValue->setOption($option);
        $signup->getFieldValues()->add($fieldValue);

        return [
            $signup,
            $fieldValue,
        ];
    }

    /**
     * Add one external sign-up to the list that answered each of its fields, by ordinal, with the given raw value (no
     * option); this is the shape a Text/Number/Yes-No answer takes.
     *
     * @param array<int, string> $valuesByOrdinal
     *
     * @return array{0: ExternalSignup, 1: list<SignupFieldValue>}
     */
    private function answerScalars(
        SignupList $list,
        array $valuesByOrdinal,
    ): array {
        $signup = new ExternalSignup();
        $signup->setSignupList($list);
        $list->getSignUps()->add($signup);

        $fieldValues = [];
        foreach ($list->getFields()->getValues() as $ordinal => $field) {
            $fieldValue = new SignupFieldValue();
            $fieldValue->setField($field);
            $fieldValue->setSignup($signup);
            $fieldValue->setValue($valuesByOrdinal[$ordinal] ?? null);
            $signup->getFieldValues()->add($fieldValue);
            $fieldValues[] = $fieldValue;
        }

        return [
            $signup,
            $fieldValues,
        ];
    }

    private function text(string $value): ActivityLocalisedText
    {
        return new ActivityLocalisedText(
            $value,
            $value,
        );
    }
}
