<?php

namespace User\Form;

use Zend\Form\Form,
    Zend\InputFilter\InputFilter;

class Login extends Form
{

    public function __construct()
    {
        parent::__construct();

        $this->add(array(
            'name' => 'login',
            'type' => 'text',
            'options' => array(
                'label' => 'Membership number or email address'
            )
        ));

        $this->add(array(
            'name' => 'password',
            'type' => 'password',
            'options' => array(
                'label' => 'Your password'
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Login'
            )
        ));

        $this->initFilters();
    }

    protected function initFilters()
    {
        $filter = new InputFilter();

        $filter->add(array(
            'name' => 'login',
            'required' => true,
            'validators' => array(
                array('name' => 'not_empty')
            )
        ));

        $filter->add(array(
            'name' => 'password',
            'required' => true,
            'validators' => array(
                array('name' => 'not_empty'),
                array(
                    'name' => 'string_length',
                    'options' => array(
                        'min' => 8
                    )
                )
            )
        ));

        $this->setInputFilter($filter);
    }
}

