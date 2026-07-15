<?php

declare(strict_types=1);

namespace App\Tests\Form\Activity;

use App\Entity\Activity\ActivityLocalisedText;
use App\Entity\Activity\Enums\SignupFieldTypes;
use App\Entity\Activity\ExternalSignup;
use App\Entity\Activity\SignupList;
use App\Form\Activity\SignupFieldType;
use App\Form\Activity\SignupListType;
use App\Form\Activity\SignupOptionType;
use App\Form\Application\LocalisedTextType;
use Override;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

use function sprintf;

/**
 * Once a sign-up list has sign-ups the way places are allocated must not change under the people who already committed:
 * the allocation method and its per-method settings are frozen (rendered read-only, ignored on submit). The capacity is
 * deliberately left editable so seats can still be adjusted while the list is open. These tests pin both.
 */
// TypeTestCase creates an unconfigured EventDispatcher mock internally; opt out of the no-expectations notice.
#[AllowMockObjectsWithoutExpectations]
final class SignupListTypeTest extends TypeTestCase
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
     * @return list<SignupListType|LocalisedTextType|SignupFieldType|SignupOptionType>
     */
    #[Override]
    protected function getTypes(): array
    {
        return [
            new SignupListType(),
            new LocalisedTextType(),
            new SignupFieldType(),
            new SignupOptionType(),
        ];
    }

    public function testAllocationMethodIsFrozenOnceTheListHasSignUps(): void
    {
        $form = $this->factory->create(
            SignupListType::class,
            $this->listWithSignUp(),
        );

        // The allocation method and every per-method setting are disabled: the deal can no longer be rewritten.
        foreach (
            [
                'allocationMethod',
                'drawCutoffRule',
                'drawCutoffAt',
                'drawAfterDurationHours',
                'externalPolicyUrl',
                'externalForceOrdering',
                'externalPaymentByExternal',
                'customMethodDescription',
            ] as $name
        ) {
            self::assertTrue(
                $this->isDisabled(
                    $form,
                    $name,
                ),
                sprintf(
                    'Expected "%s" to be frozen once the list has sign-ups.',
                    $name,
                ),
            );
        }

        // The capacity stays editable so seats can still be adjusted; likewise the safe metadata.
        foreach (
            [
                'capacity',
                'closeDate',
                'displaySubscribedNumber',
                'promoted',
            ] as $name
        ) {
            self::assertFalse(
                $this->isDisabled(
                    $form,
                    $name,
                ),
                sprintf(
                    'Expected "%s" to stay editable once the list has sign-ups.',
                    $name,
                ),
            );
        }
    }

    public function testAllocationMethodStaysEditableWhileTheListHasNoSignUps(): void
    {
        $form = $this->factory->create(
            SignupListType::class,
            $this->list(),
        );

        self::assertFalse(
            $this->isDisabled(
                $form,
                'allocationMethod',
            ),
            'A list without sign-ups must keep its allocation method editable.',
        );
    }

    public function testSubmittedFieldAndOptionOrderAndDefaultAreMappedOntoTheEntities(): void
    {
        $list = $this->list();
        $form = $this->factory->create(
            SignupListType::class,
            $list,
        );

        // Submit only the fields collection (clearMissing = false keeps the list's other values); the hidden position
        // inputs carry the dragged order as strings and the default marker is a checkbox on one option.
        $form->submit(
            [
                'fields' => [
                    [
                        'name' => [
                            'valueNL' => 'Vraag',
                            'valueEN' => 'Question',
                        ],
                        'type' => SignupFieldTypes::Choice->value,
                        'position' => '2',
                        'options' => [
                            [
                                'value' => [
                                    'valueNL' => 'A',
                                    'valueEN' => 'A',
                                ],
                                'position' => '5',
                                'isDefault' => '1',
                            ],
                            [
                                'value' => [
                                    'valueNL' => 'B',
                                    'valueEN' => 'B',
                                ],
                                'position' => '3',
                            ],
                        ],
                    ],
                ],
            ],
            false,
        );

        $fields = $list->getFields()->getValues();
        self::assertCount(
            1,
            $fields,
        );
        // The string position round-trips to the entity's int property (the HiddenType model transformer).
        self::assertSame(
            2,
            $fields[0]->getPosition(),
        );

        $options = $fields[0]->getOptions()->getValues();
        self::assertSame(
            5,
            $options[0]->getPosition(),
        );
        self::assertTrue($options[0]->isDefault());
        self::assertSame(
            3,
            $options[1]->getPosition(),
        );
        // An unchecked "default" checkbox is simply absent from the submission and maps to false.
        self::assertFalse($options[1]->isDefault());
    }

    private function listWithSignUp(): SignupList
    {
        $list = $this->list();
        $list->getSignUps()->add(new ExternalSignup());

        return $list;
    }

    private function list(): SignupList
    {
        $list = new SignupList();
        $list->setName(new ActivityLocalisedText(
            'Naam',
            'Name',
        ));

        return $list;
    }

    /**
     * @param FormInterface<mixed> $form
     */
    private function isDisabled(
        FormInterface $form,
        string $name,
    ): bool {
        return $form->get($name)->getConfig()->getDisabled();
    }
}
