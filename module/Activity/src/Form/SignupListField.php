<?php

declare(strict_types=1);

namespace Activity\Form;

use Activity\Model\SignupField as SignupFieldModel;
use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\Number;
use Laminas\Form\Element\Select;
use Laminas\Form\Element\Text;
use Laminas\Form\Fieldset;
use Laminas\Hydrator\ClassMethodsHydrator;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\Callback;
use Laminas\Validator\NotEmpty;
use Override;

use function substr_count;

class SignupListField extends Fieldset implements InputFilterProviderInterface
{
    public function __construct(private readonly Translator $translator)
    {
        parent::__construct('signupfield');

        $this->setHydrator(new ClassMethodsHydrator(false))
            ->setObject(new SignupFieldModel());

        $this->add(
            [
                'name' => 'name',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Field Name'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'sensitive',
                'type' => Checkbox::class,
                'options' => [
                    'checked_value' => '1',
                    'unchecked_value' => '0',
                ],
            ],
        );

        $this->add(
            [
                'name' => 'nameEn',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Field Name'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'type',
                'type' => Select::class,
                'options' => [
                    'value_options' => [
                        '0' => $this->translator->translate('Text'),
                        '1' => $this->translator->translate('Yes/No'),
                        '2' => $this->translator->translate('Number'),
                        '3' => $this->translator->translate('Choice'),
                    ],
                    'label' => $this->translator->translate('Type'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'minimumValue',
                'type' => Number::class,
                'options' => [
                    'label' => $this->translator->translate('Min. value'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'maximumValue',
                'type' => Number::class,
                'options' => [
                    'label' => $this->translator->translate('Max. value'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'options',
                'type' => Text::class,
                'attributes' => [
                    'placeholder' => $this->translator->translate('Option 1, Option 2, ...'),
                ],
                'options' => [
                    'label' => $this->translator->translate('Options'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'optionsEn',
                'type' => Text::class,
                'attributes' => [
                    'placeholder' => $this->translator->translate('Option 1, Option 2, ...'),
                ],
                'options' => [
                    'label' => $this->translator->translate('Options'),
                ],
            ],
        );
    }

    #[Override]
    public function getInputFilterSpecification(): array
    {
        return [
            'name' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 1,
                            'max' => 100,
                        ],
                    ],
                ],
            ],
            'nameEn' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 1,
                            'max' => 100,
                        ],
                    ],
                ],
            ],
            'type' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'Between',
                        'options' => [
                            'min' => 0,
                            'max' => 3,
                        ],
                    ],
                    ['name' => 'IsInt'],
                    [
                        'name' => Callback::class,
                        'options' => [
                            'messages' => [
                                Callback::INVALID_VALUE => $this->translator->translate(
                                    'Some of the required fields for this type are empty',
                                ),
                            ],
                            'callback' => function ($value, $context = null) {
                                return $this->fieldDependantRequired($value, $context, 'minimumValue', '2') &&
                                    $this->fieldDependantRequired($value, $context, 'maximumValue', '2');
                            },
                        ],
                    ],
                ],
            ],
            'minimumValue' => [
                'required' => false,
                'validators' => [
                    ['name' => 'IsInt'],
                ],
            ],
            'maximumValue' => [
                'required' => false,
                'validators' => [
                    ['name' => 'IsInt'],
                ],
            ],
            'optionsEn' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => Callback::class,
                        'options' => [
                            'messages' => [
                                Callback::INVALID_VALUE => $this->translator->translate(
                                    'The number of English options must equal the number of Dutch options',
                                ),
                            ],
                            'callback' => static function ($value, $context = null) {
                                return !((new NotEmpty())->isValid($context['nameEn']))
                                    || !((new NotEmpty())->isValid($context['name']))
                                    || substr_count((string) $context['options'], ',') === substr_count($value, ',');
                            },
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Tests if the child field is not empty if the current field has the test
     * value. If so, returns true else false.
     *
     * @param string $value   The value to use for validation
     * @param array  $context The field context
     * @param string $child   The name of the element to test for emptiness
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    protected function fieldDependantRequired(
        string $value,
        array $context,
        string $child,
        string $testvalue,
    ): bool {
        if ($value === $testvalue) {
            return (new NotEmpty())->isValid($context[$child]);
        }

        return true;
    }
}
