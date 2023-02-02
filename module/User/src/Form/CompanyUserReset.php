<?php

namespace User\Form;

use Laminas\Authentication\Result;
use Laminas\Form\Element\{
    Csrf,
    Email,
    Hidden,
    Password,
    Submit,
};
use Laminas\Filter\StringTrim;
use Laminas\Form\Form;
use Laminas\InputFilter\InputProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\{
    EmailAddress,
    NotEmpty,
    StringLength,
};

class CompanyUserReset extends Form implements InputProviderInterface
{
    public function __construct(private readonly Translator $translate)
    {
        parent::__construct();

        $this->add(
            [
                'name' => 'email',
                'type' => Email::class,
                'options' => [
                    'label' => $translate->translate('Email address'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $translate->translate('Request password reset'),
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
            'email' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => EmailAddress::class,
                        'options' => [
                            'messages' => [
                                'emailAddressInvalidFormat' => $this->translate->translate(
                                    'E-mail address format is not valid'
                                ),
                            ],
                        ],
                    ],
                ],
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                ],
            ],
        ];
    }
}
