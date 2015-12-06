<?php

namespace Activity\Form;

use Activity\Model\ActivityField;
use Zend\Form\Fieldset;
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;
use Zend\Validator\NotEmpty;
use Zend\InputFilter\InputFilterProviderInterface;

class ActivityFieldFieldset extends Fieldset implements InputFilterProviderInterface
{
    public function __construct() {
        
        parent::__construct('activityfield');
        
        $this->setHydrator(new ClassMethodsHydrator(false))
             ->setObject(new ActivityField());
      
        $this->add(array(
            'name' => 'name',
            'options' => array(
                'label' => 'Name'
            ),
            'attributes' => array(
                'required' => 'required'
            )
        ));
                
        $this->add([
            'name' => 'type',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => array(
                    '0' => 'Text',
                    '1' => 'Yes/No',
                    '2' => 'Number',
                    '3' => 'Choice'
                ),
                'label' => 'Type'
            )
        ]);
        
        $this->add([
            'name' => 'min. value',                          
            'options' => array(
                'label' => 'Min. value'
            )
        ]);
        
        $this->add([
            'name' => 'max. value',
            'options' => array(
                'label' => 'Max. value'
            )
        ]);
        
        $this->add([
            'name' => 'options',            
            'options' => array(
                'label' => 'Options'
            )
        ]);
    }
    

    /**
     * @return array
     */
    public function getInputFilterSpecification() {
        
        return [
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
    public function fieldDependantRequired($value, $context, $child, $testvalue){
        
        if ($value === $testvalue){
            return (new NotEmpty())->isValid($context[$child]);
        }
            
        return true;
    }
}