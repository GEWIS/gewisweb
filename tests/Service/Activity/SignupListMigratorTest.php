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
use App\Service\Activity\SignupListMigrator;
use Override;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Uid\Uuid;

/**
 * The migrator re-points live sign-ups onto an approved revision's lineage-matched list clone purely by ordinal, which
 * is only safe while the layouts are provably identical. These tests pin that: a faithful clone migrates, and any
 * divergence a race or request tampering could introduce past the structural freeze (a reordered or renamed
 * field/option) hard-fails rather than silently corrupting an answer.
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

    private function revisionWith(SignupList $list): ActivityRevision
    {
        $revision = new ActivityRevision();
        $revision->addSignupList($list);

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
        $list = new SignupList();
        $list->setLineageId($lineageId);

        $field = new SignupField();
        $field->setName($this->text($fieldName));
        $field->setType(SignupFieldTypes::Choice);

        foreach ($optionLabels as $label) {
            $option = new SignupOption();
            $option->setValue($this->text($label));
            $field->addOption($option);
        }

        $list->addField($field);

        return $list;
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

    private function text(string $value): ActivityLocalisedText
    {
        return new ActivityLocalisedText(
            $value,
            $value,
        );
    }
}
