<?php

namespace Activity\Form;

use Activity\Model\ActivityField;
use Zend\Form\Fieldset;
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;
use Zend\InputFilter\FieldDependantValidator;
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
                'allowEmpty' => false,
                'label' => 'Min. value'
            )
        ]);
        
        $this->add([
            'name' => 'max. value',
            'options' => array(
                'allowEmpty' => false,
                'label' => 'Max. value'
            )
        ]);
        
        $this->add([
            'name' => 'options',            
            'options' => array(
                'allowEmpty' => false,
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
                ]
            ],
            'min. value' => [
                'validators' => [
                    new FieldDependantValidator('type', '2')
                ]
            ],
            'max. value' => [
                'validators' => [
                    new FieldDependantValidator('type', '2')
                ]
            ],
            'options' => [
                'validators' => [
                    new FieldDependantValidator('type', '3')
                ]
            ]//*/
        ];
    }

}