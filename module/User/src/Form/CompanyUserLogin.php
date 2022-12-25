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
    /**
     * @var Translator
     */
    protected Translator $translate;

    /**
     * @param Translator $translate
     */
    public function __construct(Translator $translate)
    {
        parent::__construct();
        $this->translate = $translate;

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
                'name' => 'password',
                'type' => Password::class,
                'options' => [
                    'label' => $translate->translate('Password'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $translate->translate('Login'),
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
                case Result::FAILURE_IDENTITY_NOT_FOUND:
                    $this->setMessages(
                        [
                            'login' => [
                                $this->translate->translate('This company could not be found.'),
                            ],
                        ]
                    );
                    break;
                case Result::FAILURE_CREDENTIAL_INVALID:
                    $this->setMessages(
                        [
                            'password' => [
                                $this->translate->translate('Wrong password provided.'),
                            ],
                        ]
                    );
                    break;
                case Result::FAILURE:
                    $this->setMessages(
                        [
                            'password' => [
                                $this->translate->translate('Too many login attempts, try again later.'),
                            ],
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
        ];
    }
}
