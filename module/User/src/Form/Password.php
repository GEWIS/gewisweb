<?php

namespace User\Form;

use Laminas\Form\Element\{
    Password as PasswordElement,
    Submit,
};
use Laminas\Form\Form;
use Laminas\Mvc\I18n\Translator;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\{
    Identical,
    NotEmpty,
    StringLength,
};

class Password extends Form implements InputFilterProviderInterface
{
    /**
     * @param Translator $translate
     */
    public function __construct(Translator $translate)
    {
        parent::__construct();

        $this->add(
            [
                'name' => 'old_password',
                'type' => PasswordElement::class,
                'options' => [
                    'label' => $translate->translate('Old password'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'password',
                'type' => PasswordElement::class,
                'options' => [
                    'label' => $translate->translate('New password'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'password_verify',
                'type' => PasswordElement::class,
                'options' => [
                    'label' => $translate->translate('Verify new password'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $translate->translate('Change password'),
                ],
            ]
        );
    }

    /**
     * @return array
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'password' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => NotEmpty::class,
                    ],
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
                    [
                        'name' => NotEmpty::class,
                    ],
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 8,
                        ],
                    ],
                    [
                        'name' => Identical::class,
                        'options' => [
                            'token' => 'password',
                        ],
                    ],
                ],
            ],
        ];
    }
}
