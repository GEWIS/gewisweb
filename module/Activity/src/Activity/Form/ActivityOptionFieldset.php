<?php

namespace Activity\Form;

use Model\ActivityField;
use Zend\Form\Fieldset;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;

class ActivityOptionFieldset extends Fieldset implements InputFilterProviderInterface
{
    public function __construct() {
        
        parent::__construct('activityoption');
        
        $this->setHydrator(new ClassMethodsHydrator(false))
             ->setObject(new Category());
        
        $this->add([
            'name' => 'name',
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