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
