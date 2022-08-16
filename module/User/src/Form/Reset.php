<?php

namespace User\Form;

use Laminas\Form\Element\{
    Csrf,
    Email,
    Number,
    Submit,
};
use Laminas\Form\Form;
use Laminas\InputFilter\InputProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\{
    Digits,
    EmailAddress,
    NotEmpty,
};

class Reset extends Form implements InputProviderInterface
{
    public function __construct(private readonly Translator $translator)
    {
        parent::__construct();

        $this->add(
            [
                'name' => 'lidnr',
                'type' => Number::class,
                'options' => [
                    'label' => $this->translator->translate('Membership number'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'email',
                'type' => Email::class,
                'options' => [
                    'label' => $this->translator->translate('E-mail address'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $this->translator->translate('Reset password'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'security',
                'type' => Csrf::class,
            ]
        );
    }

    /**
     * @return array
     */
    public function getInputSpecification(): array
    {
        return [
            'lidnr' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => NotEmpty::class,
                    ],
                    [
                        'name' => Digits::class,
                    ],
                ],
            ],
            'email' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => NotEmpty::class,
                    ],
                    [
                        'name' => EmailAddress::class,
                    ],
                ],
            ],
        ];
    }
}
