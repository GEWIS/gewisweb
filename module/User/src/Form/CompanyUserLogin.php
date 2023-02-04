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

class CompanyUserLogin extends Form implements InputProviderInterface
{
    public function __construct(
        private readonly Translator $translator,
        private readonly int $passwordLength,
    ) {
        parent::__construct();

        $this->add(
            [
                'name' => 'email',
                'type' => Email::class,
                'options' => [
                    'label' => $translator->translate('Email address'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'password',
                'type' => Password::class,
                'options' => [
                    'label' => $translator->translate('Password'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $translator->translate('Log in as company'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'redirect',
                'type' => Hidden::class,
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
     * Set authentication result.
     *
     * @param Result $result
     */
    public function setResult(Result $result): void
    {
        if (!$result->isValid()) {
            switch ($result->getCode()) {
                case Result::FAILURE:
                case Result::FAILURE_IDENTITY_NOT_FOUND:
                    $this->setMessages(
                        [
                            'login' => $result->getMessages(),
                        ]
                    );
                    break;
                case Result::FAILURE_CREDENTIAL_INVALID:
                    $this->setMessages(
                        [
                            'password' => $result->getMessages(),
                        ]
                    );
                    break;
            }
        }
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
                                'emailAddressInvalidFormat' => $this->translator->translate(
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
        ];
    }
}
