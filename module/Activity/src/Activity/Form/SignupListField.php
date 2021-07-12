<?php

namespace Activity\Form;

use Activity\Model\SignupField;
use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Hydrator\ClassMethodsHydrator;
use Laminas\Validator\Callback;
use Laminas\Validator\NotEmpty;

class SignupListField extends Fieldset implements InputFilterProviderInterface
{
    /**
     * @var Translator
     */
    protected $translator;

    public function __construct(Translator $translator)
    {
        parent::__construct('signupfield');
        $this->translator = $translator;
        $this->setHydrator(new ClassMethodsHydrator(false))
            ->setObject(new SignupField());

        $this->add(
            [
            'name' => 'name',
            'attributes' => [
                'type' => 'text',
            ],
            'options' => [
                'label' => $translator->translate('Name'),
            ],
            ]
        );

        $this->add(
            [
            'name' => 'nameEn',
            'attributes' => [
                'type' => 'text',
            ],
            'options' => [
                'label' => $translator->translate('Name'),
            ],
            ]
        );

        $this->add(
            [
            'name' => 'type',
            'type' => 'Laminas\Form\Element\Select',
            'options' => [
                'value_options' => [
                    '0' => $translator->translate('Text'),
                    '1' => $translator->translate('Yes/No'),
                    '2' => $translator->translate('Number'),
                    '3' => $translator->translate('Choice'),
                ],
                'label' => $translator->translate('Type'),
            ]
            ]
        );

        $this->add(
            [
            'name' => 'minimumValue',
            'attributes' => [
                'type' => 'number',
            ],
            'options' => [
                'label' => $translator->translate('Min. value'),
            ],
            ]
        );

        $this->add(
            [
            'name' => 'maximumValue',
            'attributes' => [
                'type' => 'number',
            ],
            'options' => [
                'label' => $translator->translate('Max. value'),
            ],
            ]
        );

        $this->add(
            [
            'name' => 'options',
            'attributes' => [
                'placeholder' => $translator->translate('Option 1, Option 2, ...'),
                'type' => 'text',
            ],
            'options' => [
                'label' => $translator->translate('Options'),
            ],
            ]
        );

        $this->add(
            [
            'name' => 'optionsEn',
            'attributes' => [
                'placeholder' => $translator->translate('Option 1, Option 2, ...'),
                'type' => 'text',
            ],
            'options' => [
                'label' => $translator->translate('Options'),
            ],
            ]
        );
    }

    /**
     * @return array
     */
    public function getInputFilterSpecification()
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
                            'max' => 3
                        ]
                    ],
                    ['name' => 'IsInt'],
                    [
                        'name' => Callback::class,
                        'options' => [
                            'messages' => [
                                Callback::INVALID_VALUE =>
                                    'Some of the required fields for this type are empty'
                            ],
                            'callback' => function ($value, $context = null) {
                                return $this->fieldDependantRequired($value, $context, 'minimumValue', '2') &&
                                    $this->fieldDependantRequired($value, $context, 'maximumValue', '2');
                            }
                        ]
                    ]
                ]
            ],
            'minimumValue' => [
                'required' => false,
                'validators' => [
                    ['name' => 'IsInt']
                ]
            ],
            'maximumValue' => [
                'required' => false,
                'validators' => [
                    ['name' => 'IsInt']
                ]
            ],
            'optionsEn' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => Callback::class,
                        'options' => [
                            'messages' => [
                                Callback::INVALID_VALUE =>
                                    'The number of English options must equal the number of Dutch options'
                            ],
                            'callback' => function ($value, $context = null) {
                                return !((new NotEmpty())->isValid($context['nameEn']))
                                    || !((new NotEmpty())->isValid($context['name']))
                                    || substr_count($context['options'], ",") === substr_count($value, ",");
                            }
                        ]
                    ]
                ]
            ],
        ];
    }

    /**
     * Tests if the child field is not empty if the current field has the test
     * value. If so, returns true else false.
     *
     * @param string $value The value to use for validation
     * @param array $context The field context
     * @param string $child The name of the element to test for emptiness
     * @param string $testvalue
     * @return boolean
     */
    protected function fieldDependantRequired($value, $context, $child, $testvalue)
    {
        if ($value === $testvalue) {
            return (new NotEmpty())->isValid($context[$child]);
        }

        return true;
    }
}
