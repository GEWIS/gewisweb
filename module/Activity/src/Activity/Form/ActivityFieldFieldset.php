<?php

namespace Activity\Form;

use Model\ActivityField;
use Zend\Form\Fieldset;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;

class ActivityFieldFieldset extends Fieldset implements InputFilterProviderInterface
{
    public function __construct() {
        
        parent::__construct('activityfield');
        
        $this->setHydrator(new ClassMethodsHydrator(false))
             ->setObject(new ActivityField());
        
        $this->add([
            'name' => 'Name',
            'options' => array(
                'label' => 'Name of the field'
            ),
            'attributes' => array(
                'required' => 'required'
            )
        ]);
                
        $this->add([
            'name' => 'Type',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'label' => 'Type of the field',
                'value_options' => array(
                    '0' => 'Text',
                    '1' => 'Yes/No',
                    '2' => 'Number',
                    '3' => 'Choice'
                )
            ),
            'attributes' => array(
                'required' => 'required'
            )
        ]);
        
        $this->add([
            'name' => 'Min. value',
            'attributes' => array(
                'required' => 'required'
            )
        ]);
        
        $this->add([
            'name' => 'Max. value',
            'attributes' => array(
                'required' => 'required'
            )
        ]);
        
        $this->add([
            'name' => 'Options',
            'type' => 'Zend\Form\Element\Collection',
            'options' => array(
                'count' => 0,
                'should_create_template' => true,
                'allow_add' => true,
                'target_element' => array(
                    'type' => 'Activity\Form\ActivityOptionFieldset'
                )
            )
        ]);
    }
    
    
    public function getInputFilterSpecification() {
        
    }

}