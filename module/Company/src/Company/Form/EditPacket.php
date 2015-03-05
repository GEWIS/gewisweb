<?php
namespace Company\Form;

use Zend\Form\Element;
use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\Mvc\I18n\Translator;

class EditPacket extends Form
{
    public function __construct(Translator $translate)
    {
        // we want to ignore the name passed
        parent::__construct();
        
        $this->setAttribute('method', 'post');
        $this->add(array(
            'name' => 'id',
            'attributes' => array(
                'type'  => 'hidden',
            ),
        ));
        $this->add(array(
            'name' => 'startDate',
            'type' => 'Zend\Form\Element\Date', 
            'attributes' => array( 
                'required' => 'required', 
                'step' => '1', 
            ), 
            'options' => array( 
                'label' => $translate->translate('Start date'),
            ), 
        ));
        $this->add(array(
            'name' => 'expirationDate',
            'type' => 'Zend\Form\Element\Date', 
            'attributes' => array( 
                'required' => 'required', 
                'step' => '1', 
            ), 
            'options' => array( 
                'label' => $translate->translate('Expiration date'),
            ), 
        ));
        $this->add(array(
            'name' => 'published',
            'type' => 'Zend\Form\Element\Checkbox',
            'attributes' => array(
                'required' => 'required',
            ),
            'options' => array(
                'label' => $translate->translate('Published'),
                'required' => 'required',
                'value_options' => array(
                    '0' => 'Enabled', 
                ),
            ),
        ));
        
        $this->initFilters();
    }
    
    protected function initFilters()
    {
        $filter = new InputFilter();


        $filter->add(array(
            'name' => 'startDate',
            'required' => true,
            'validators' => array(
            ),
            'filters' => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim')
            ),
        ));
        
        $filter->add(array(
            'name' => 'expirationDate',
            'required' => true,
            'validators' => array(
            ),
            'filters' => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim')
            ),
        ));
        
        $this->setInputFilter($filter);
    }
}
