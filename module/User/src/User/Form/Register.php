<?php

namespace User\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\I18n\Translator\Translator;

class Register extends Form
{

    public function __construct(Translator $translate)
    {
        parent::__construct();

        $this->add(array(
            'name' => 'lidnr',
            'type' => 'number',
            'options' => array(
                'label' => $translate->translate('Membership number')
            )
        ));

        $this->add(array(
            'name' => 'email',
            'type' => 'email',
            'options' => array(
                'label' => $translate->translate('E-mail address')
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => $translate->translate('Register')
            )
        ));

        $this->initFilters();
    }

    protected function initFilters()
    {
        $filter = new InputFilter();

        $filter->add(array(
            'name' => 'lidnr',
            'required' => true,
            'validators' => array(
                array('name' => 'not_empty'),
                array('name' => 'digits')
            )
        ));

        $filter->add(array(
            'name' => 'email',
            'required' => true,
            'validators' => array(
                array('name' => 'not_empty'),
                array('name' => 'email_address')
            )
        ));

        $this->setInputFilter($filter);
    }
}


