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
             ->setObject(new \Activity\Model\ActivityField());
      
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
            'name' => 'Type',
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
            'name' => 'Min. value',
            'options' => array(
                'label' => 'Min. value'
            )
        ]);
        
        $this->add([
            'name' => 'Max. value',
            'options' => array(
                'label' => 'Max. value'
            )
        ]);
        
        $this->add([
            'name' => 'Options',            
            'options' => array(
                'label' => 'Options'
            )
        ]);
    }
    
    
    public function getInputFilterSpecification() {
        return array(
            'name' => array(
                'required' => true
            )
        );
    }

}