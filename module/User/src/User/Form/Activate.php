<?php

namespace User\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\I18n\Translator\TranslatorInterface as Translator;
use Zend\Validator\NotEmpty;
use Zend\Validator\StringLength;

class Activate extends Form implements InputFilterProviderInterface
{
    public function __construct(Translator $translate)
    {
        parent::__construct();

        $this->add(
            [
            'name' => 'password',
            'type' => 'password',
            'options' => [
                'label' => $translate->translate('Your password')
            ]
            ]
        );

        $this->add(
            [
            'name' => 'password_verify',
            'type' => 'password',
            'options' => [
                'label' => $translate->translate('Verify your password')
            ]
            ]
        );

        $this->add(
            [
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => $translate->translate('Activate')
            ]
            ]
        );
    }

    public function getInputFilterSpecification()
    {
        return [
            'password' => [
                'required' => true,
                'validators' => [
                    ['name' => NotEmpty::class],
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 8
                        ]
                    ]
                ]
            ],
            'password_verify' => [
                'required' => true,
                'validators' => [
                    ['name' => NotEmpty::class],
                    [
                        'name' => StringLength::class,
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
            ]
        ];
    }
}
