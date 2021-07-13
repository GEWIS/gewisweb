<?php

namespace User\Form;

use Laminas\Form\Form;
use Laminas\I18n\Translator\TranslatorInterface as Translator;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\StringLength;

class Password extends Form implements InputFilterProviderInterface
{
    public function __construct(Translator $translate)
    {
        parent::__construct();

        $this->add(
            [
            'name' => 'old_password',
            'type' => 'password',
            'options' => [
                'label' => $translate->translate('Old password'),
            ],
            ]
        );

        $this->add(
            [
            'name' => 'password',
            'type' => 'password',
            'options' => [
                'label' => $translate->translate('New password'),
            ],
            ]
        );

        $this->add(
            [
            'name' => 'password_verify',
            'type' => 'password',
            'options' => [
                'label' => $translate->translate('Verify new password'),
            ],
            ]
        );

        $this->add(
            [
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => $translate->translate('Change password'),
            ],
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
                            'min' => 8,
                        ],
                    ],
                ],
            ],
            'password_verify' => [
                'required' => true,
                'validators' => [
                    ['name' => NotEmpty::class],
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 8,
                        ],
                    ],
                    [
                        'name' => 'identical',
                        'options' => [
                            'token' => 'password',
                        ],
                    ],
                ],
            ],
        ];
    }
}
