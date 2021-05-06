<?php

namespace Activity\Form;

use Activity\Model\ActivityField;
use Zend\Form\Fieldset;
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;
use Doctrine\Common\Persistence\ObjectManager;
use Zend\Validator\NotEmpty;
use Zend\InputFilter\InputFilterProviderInterface;

class ActivityFieldFieldset extends Fieldset implements InputFilterProviderInterface
{
    public function __construct(ObjectManager $objectManager) {

        parent::__construct('activityfield');
        $this->setHydrator(new ClassMethodsHydrator(false))
              ->setObject(new ActivityField());
        $this->add([
            'name' => 'name',
            'options' => ['label' => 'Name'],
        ]);

        $this->add([
            'name' => 'nameEn',
            'options' => ['label' => 'Name(English)'],
        ]);

        $this->add([
            'name' => 'type',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'value_options' => [
                    '0' => 'Text',
                    '1' => 'Yes/No',
                    '2' => 'Number',
                    '3' => 'Choice'
                ],
                'label' => 'Type'
            ]
        ]);

        $this->add([
            'name' => 'min. value',
            'options' => [
                'label' => 'Min. value'
            ]
        ]);

        $this->add([
            'name' => 'max. value',
            'options' => [
                'label' => 'Max. value'
            ]
        ]);

        $this->add([
            'name' => 'options',
            'options' => [
                'label' => 'Options'
            ]
        ]);

        $this->add([
            'name' => 'optionsEn',
            'options' => [
                'label' => 'Options (English)'
            ]
        ]);
    }


    /**
     * @return array
     */
    public function getInputFilterSpecification() {

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
                        'name' => 'Callback',
                        'options' => [
                            'messages' => [
                            \Zend\Validator\Callback::INVALID_VALUE =>
                                'Some of the required fields for this type are empty'
                            ],
                            'callback' => function($value, $context=null) {
                                return $this->fieldDependantRequired($value, $context, 'min. value', '2') &&
                                       $this->fieldDependantRequired($value, $context, 'max. value', '2');
                            }
                        ]
                    ]
                ]
            ],
            'min. value' => [
                'required' => false,
                'validators' => [
                    ['name' => 'IsInt']
                ]
            ],
            'max. value' => [
                'required' => false,
                'validators' => [
                    ['name' => 'IsInt']
                ]
            ],
            'optionsEn' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => 'Callback',
                        'options' => [
                            'messages' => [
                                \Zend\Validator\Callback::INVALID_VALUE =>
                                    'The number of English options must equal the number of Dutch options'
                            ],
                            'callback' => function ($value, $context=null) {
                                return !((new NotEmpty())->isValid($context['nameEn']))
                                    || substr_count($context['options'],",") === substr_count($value,",");
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
    protected function fieldDependantRequired($value, $context, $child, $testvalue) {

        if ($value === $testvalue){
            return (new NotEmpty())->isValid($context[$child]);
        }

        return true;
    }
}