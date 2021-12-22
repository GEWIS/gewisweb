<?php

namespace User\Form;

use Laminas\Form\Element\{
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

class Register extends Form implements InputProviderInterface
{
    public const ERROR_WRONG_EMAIL = 'wrong_email';
    public const ERROR_MEMBER_NOT_EXISTS = 'member_not_exists';
    public const ERROR_USER_ALREADY_EXISTS = 'user_already_exists';
    public const ERROR_ALREADY_REGISTERED = 'already_registered';

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
                'name' => 'lidnr',
                'type' => Number::class,
                'options' => [
                    'label' => $translate->translate('Membership number'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'email',
                'type' => Email::class,
                'options' => [
                    'label' => $translate->translate('E-mail address'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $translate->translate('Register'),
                ],
            ]
        );
    }

    /**
     * Set the error.
     *
     * @param string $error
     */
    public function setError(string $error): void
    {
        switch ($error) {
            case self::ERROR_WRONG_EMAIL:
                $this->setMessages(
                    [
                        'email' => [
                            $this->translate->translate('This email address does not be long to the given member.'),
                        ],
                    ]
                );
                break;
            case self::ERROR_MEMBER_NOT_EXISTS:
                $this->setMessages(
                    [
                        'lidnr' => [
                            $this->translate->translate('There is no member with this membership number.'),
                        ],
                    ]
                );
                break;
            case self::ERROR_ALREADY_REGISTERED:
                $this->setMessages(
                    [
                        'lidnr' => [
                            $this->translate->translate('You already attempted to register, please check your email or try again after 15 minutes.'),
                        ],
                    ]
                );
                break;
            case self::ERROR_USER_ALREADY_EXISTS:
                $this->setMessages(
                    [
                        'lidnr' => [
                            $this->translate->translate('This member already has an account.'),
                        ],
                    ]
                );
                break;
        }
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
