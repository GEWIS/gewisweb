<?php

namespace Activity\Form;

use Zend\Form\Form;
//input filter
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\CollectionInputFilter;
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;
use Zend\InputFilter\InputFilterProviderInterface;

class ActivitySignup extends Form implements InputFilterProviderInterface
{
    protected $inputFilter;
    protected $fields;
    public function __construct($fields)
    {
        parent::__construct('activitysignup');
        $this->setAttribute('method', 'post');
        $this->setHydrator(new ClassMethodsHydrator(false))
            ->setObject(new \Activity\Model\ActivitySignup());
        $this->fields = $fields;
        foreach($fields as $field){
            $this->add($this->createFieldElementArray($field));
        }
        
        $this->add([
            'name' => 'submit',
            'attributes' => [
                'type' => 'submit',
                'value' => 'Subscribe',
            ],
        ]);
    }
/*
 *         return [
            'name' => [
                'required' => true
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
                                       $this->fieldDependantRequired($value, $context, 'max. value', '2') &&
                                       $this->fieldDependantRequired($value, $context, 'options', '3');
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
            ]                    
        ];
    }
*/
    public function getInputFilterSpecification()
    {
        $res = [];
        foreach($this->fields as $field){
            $entry = [];
            $entry['required'] = true;
            switch ($field->get('type')) {
                case 0://'Text'
                    $entry['validators'] = [
                        [
                            'name' => 'StringLength',
                            'options' => [
                                'encoding' => 'UTF-8',
                                'min' => 1,
                                'max' => 100,
                            ],
                        ]
                    ];
                    break;
                case 1://'Yes/No'
                    $entry['validators'] = [
                        [
                            'name' => 'Between',
                            'options' => [
                                'min' => 0,
                                'max' => 1
                            ],
                        ],
                        [ 'name' => 'IsInt']
                    ];
                    break;
                case 2://'Number'
                    $entry['validators'] = [
                       [
                           'name' => 'Between',
                           'options' => [
                               'min' => $field->get('minimumValue'),
                               'max' => $field->get('maximumValue')
                           ]
                       ],
                       [ 'name' => 'IsFloat']
                    ];
                    break;
                case 3://'Option'
                    $entry['validators'] = [
                        [ 'name' => 'IsInt']
                    ];
                    break;
            }
            $res[$field->get('id')] = $entry;
        }
        //TODO: Make validator work.
        return [];
        //return $res;
            
    }
    
    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        throw new \Exception('Not used');
    }
    
    /**
     * Creates an array of the form element specification for the given $field,
     * to be used by the factory.
     * 
     * @param \Activity\Model\ActivityField $field
     * @return array 
     */
    protected function createFieldElementArray(\Activity\Model\ActivityField $field){
        
        $result = [
            'name' => $field->get('id'),
        ];
        switch($field->get('type')){
            case 0: //'Text'
                $result['type'] = 'Text';
                //$result['options'] = [];
                break;
            case 1: //'Yes/No'
                $result['type'] = 'Zend\Form\Element\Radio';
                $result['options'] = [
                    'value_options' => [
                        '1' => 'Yes',
                        '0' => 'No',
                    ]
                ];
                break;
            case 2: //'Number'
                $result['type'] = 'Zend\Form\Element\Number';
                $result['attributes'] = [
                    'min' => $field->get('minimumValue'),
                    'max' => $field->get('maximumValue'),
                    'step' => '0.01'
                ];
                break;
            case 3: //'Choice'
                $values = [];
                foreach($field->get('options') as $option){
                    $values[$option->get('id')] = $option->get('value');
                }
                $result['type'] = 'Zend\Form\Element\Select';
                $result['options'] = [
                    'empty_option' => 'Make a choice',
                    'value_options' => $values
                ];
                break;
        }

        return $result;
    }
}

