<?php

namespace User\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\I18n\Translator\Translator;

class Login extends Form
{

    public function __construct(Translator $translate)
    {
        parent::__construct();

        $this->add(array(
            'name' => 'login',
            'type' => 'text',
            'options' => array(
                'label' => $translate->translate('Membership number or email address')
            )
        ));

        $this->add(array(
            'name' => 'password',
            'type' => 'password',
            'options' => array(
                'label' => $translate->translate('Your password')
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => $translate->translate('Login')
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

