<?php

declare(strict_types=1);

namespace App\Tests\Form\Activity;

use App\Entity\Activity\ActivityLocalisedText;
use App\Entity\Activity\Enums\SignupFieldTypes;
use App\Entity\Activity\SignupField;
use App\Entity\Activity\SignupList;
use App\Entity\Activity\SignupOption;
use App\Form\Activity\SignupType;
use Override;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use ReflectionProperty;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A choice field's options are rendered by (localised) label but submitted by option id. Two options may legitimately
 * share a label, so keying the choices by label would silently collapse them into a single selectable option: this test
 * pins that both same-label options survive as distinct, submittable choices keyed by their ids.
 */
// TypeTestCase creates an unconfigured EventDispatcher mock internally; opt out of the no-expectations notice.
#[AllowMockObjectsWithoutExpectations]
final class SignupTypeTest extends TypeTestCase
{
    /**
     * @return list<FormExtensionInterface>
     */
    #[Override]
    protected function getExtensions(): array
    {
        return [
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    /**
     * @return list<SignupType>
     */
    #[Override]
    protected function getTypes(): array
    {
        $translator = self::createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        return [new SignupType($translator)];
    }

    public function testTwoOptionsSharingALabelStayDistinctSubmittableChoices(): void
    {
        $field = $this->choiceFieldWithDuplicateLabels();
        $list = new SignupList();
        $list->addField($field);

        $form = $this->factory->create(
            SignupType::class,
            null,
            [
                'signupList' => $list,
                'mode' => SignupType::MODE_MEMBER,
            ],
        );

        $choices = $form->get(SignupType::fieldKey(100))->getConfig()->getOption('choices');

        // Both option ids remain selectable despite the identical label; a label-keyed map would have left only one.
        self::assertSame(
            [
                201,
                202,
            ],
            $choices,
        );

        // The second same-label option is genuinely submittable and binds back to its own id (the policy agreement,
        // required in member mode, is checked so the whole form validates).
        $form->submit([
            SignupType::fieldKey(100) => '202',
            'agree' => '1',
        ]);

        self::assertTrue($form->isValid());
        self::assertSame(
            202,
            $form->get(SignupType::fieldKey(100))->getData(),
        );
    }

    public function testDefaultOptionIsPreselectedForANewSignupAndDropsThePlaceholder(): void
    {
        $list = new SignupList();
        $list->addField($this->choiceField(
            100,
            [
                201 => false,
                202 => true,
                203 => false,
            ],
        ));

        // A brand-new sign-up: no prefill data, so the field-level default takes effect.
        $form = $this->factory->create(
            SignupType::class,
            null,
            [
                'signupList' => $list,
                'mode' => SignupType::MODE_MEMBER,
            ],
        );

        $choice = $form->get(SignupType::fieldKey(100));

        // The flagged option is preselected...
        self::assertSame(
            202,
            $choice->getData(),
        );
        // ...and the empty placeholder is dropped (ChoiceType normalises `false` to null), so a real option is always
        // selected.
        self::assertNull($choice->getConfig()->getOption('placeholder'));
    }

    public function testAnExistingAnswerOverridesTheDefaultOption(): void
    {
        $list = new SignupList();
        $list->addField($this->choiceField(
            100,
            [
                201 => false,
                202 => true,
                203 => false,
            ],
        ));

        // Editing an existing sign-up whose saved answer is a non-default option; the prefill data must win.
        $form = $this->factory->create(
            SignupType::class,
            [SignupType::fieldKey(100) => 203],
            [
                'signupList' => $list,
                'mode' => SignupType::MODE_MEMBER,
            ],
        );

        self::assertSame(
            203,
            $form->get(SignupType::fieldKey(100))->getData(),
        );
    }

    public function testWithoutADefaultTheChoiceKeepsItsPlaceholderAndNoPreselection(): void
    {
        $list = new SignupList();
        $list->addField($this->choiceField(
            100,
            [
                201 => false,
                202 => false,
            ],
        ));

        $form = $this->factory->create(
            SignupType::class,
            null,
            [
                'signupList' => $list,
                'mode' => SignupType::MODE_MEMBER,
            ],
        );

        $choice = $form->get(SignupType::fieldKey(100));

        self::assertNull($choice->getData());
        // The stub translator echoes the message key, so the placeholder is present (not dropped).
        self::assertSame(
            'Choose an option',
            $choice->getConfig()->getOption('placeholder'),
        );
    }

    /**
     * A choice field with the given options, each flagged (or not) as the default.
     *
     * @param array<int, bool> $options option id => whether it is the default
     */
    private function choiceField(
        int $fieldId,
        array $options,
    ): SignupField {
        $field = new SignupField();
        $this->setId(
            $field,
            $fieldId,
        );
        $field->setName(new ActivityLocalisedText(
            'Kleur',
            'Colour',
        ));
        $field->setType(SignupFieldTypes::Choice);

        foreach ($options as $optionId => $isDefault) {
            $option = new SignupOption();
            $this->setId(
                $option,
                $optionId,
            );
            $option->setValue(new ActivityLocalisedText(
                'Waarde ' . $optionId,
                'Value ' . $optionId,
            ));
            $option->setIsDefault($isDefault);
            $field->addOption($option);
        }

        return $field;
    }

    private function choiceFieldWithDuplicateLabels(): SignupField
    {
        $field = new SignupField();
        $this->setId(
            $field,
            100,
        );
        $field->setName(new ActivityLocalisedText(
            'Kleur',
            'Colour',
        ));
        $field->setType(SignupFieldTypes::Choice);

        foreach (
            [
                201,
                202,
            ] as $optionId
        ) {
            $option = new SignupOption();
            $this->setId(
                $option,
                $optionId,
            );
            // Deliberately identical labels on both options.
            $option->setValue(new ActivityLocalisedText(
                'Zelfde',
                'Same',
            ));
            $field->addOption($option);
        }

        return $field;
    }

    private function setId(
        object $entity,
        int $id,
    ): void {
        // Entities auto-generate their id on persist; the form keys choices by id, so a unit test must assign one.
        $property = new ReflectionProperty(
            $entity,
            'id',
        );
        $property->setValue(
            $entity,
            $id,
        );
    }
}
