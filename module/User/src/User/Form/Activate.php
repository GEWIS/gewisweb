<?php

namespace User\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\I18n\Translator\TranslatorInterface as Translator;
use Zend\Authentication\Result;

class Activate extends Form
{

    public function __construct(Translator $translate)
    {
        parent::__construct();

        $this->add([
            'name' => 'password',
            'type' => 'password',
            'options' => [
                'label' => $translate->translate('Your password')
            ]
        ]);

        $this->add([
            'name' => 'password_verify',
            'type' => 'password',
            'options' => [
                'label' => $translate->translate('Verify your password')
            ]
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => $translate->translate('Activate')
            ]
        ]);

        $this->initFilters();
    }

    protected function initFilters()
    {
        $filter = new InputFilter();

        $filter->add([
            'name' => 'password',
            'required' => true,
            'validators' => [
                ['name' => 'not_empty'],
                [
                    'name' => 'string_length',
                    'options' => [
                        'min' => 8
                    ]
                ]
            ]
        ]);

        $filter->add([
            'name' => 'password_verify',
            'required' => true,
            'validators' => [
                ['name' => 'not_empty'],
                [
                    'name' => 'string_length',
                    'options' => [
                        'min' => 8
                    ]
                ],
                [
                    'name' => 'identical',
                    'options' => [
                        'token' => 'password'
                    ]
                ]

            ]
        ]);

        $this->setInputFilter($filter);
    }
}
