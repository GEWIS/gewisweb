<?php

namespace User\Form;

use Laminas\Form\Element\{
    Password,
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

class Activate extends Form implements InputFilterProviderInterface
{
    public function __construct(
        private readonly Translator $translator,
        private readonly int $passwordLength,
    ) {
        parent::__construct();

        $this->add(
            [
                'name' => 'password',
                'type' => Password::class,
                'options' => [
                    'label' => $this->translator->translate('Your password'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'password_verify',
                'type' => Password::class,
                'options' => [
                    'label' => $this->translator->translate('Verify your password'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $this->translator->translate('Activate'),
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
                            'min' => $this->passwordLength,
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
                            'min' => $this->passwordLength,
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
