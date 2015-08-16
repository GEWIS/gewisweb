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
            'attributes' => array(
                'required' => 'required'
            ),
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
            'attributes' => array(
                'required' => 'required'
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