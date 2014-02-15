<?php

namespace User\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\I18n\Translator\Translator;
use Zend\Authentication\Result;

class Activate extends Form
{

    public function __construct(Translator $translate)
    {
        parent::__construct();

        $this->add(array(
            'name' => 'password',
            'type' => 'password',
            'options' => array(
                'label' => $translate->translate('Your password')
            )
        ));

        $this->add(array(
            'name' => 'password_verify',
            'type' => 'password',
            'options' => array(
                'label' => $translate->translate('Verify your password')
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => $translate->translate('Activate')
            )
        ));

        $this->initFilters();
    }

    protected function initFilters()
    {
        $filter = new InputFilter();

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

        $filter->add(array(
            'name' => 'password_verify',
            'required' => true,
            'validators' => array(
                array('name' => 'not_empty'),
                array(
                    'name' => 'string_length',
                    'options' => array(
                        'min' => 8
                    )
                ),
                array(
                    'name' => 'identical',
                    'options' => array(
                        'token' => 'password'
                    )
                )

            )
        ));

        $this->setInputFilter($filter);
    }
}
