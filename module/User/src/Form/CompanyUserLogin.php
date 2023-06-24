<?php

declare(strict_types=1);

namespace User\Form;

use Laminas\Authentication\Result;
use Laminas\Filter\StringTrim;
use Laminas\Form\Element\Csrf;
use Laminas\Form\Element\Email;
use Laminas\Form\Element\Hidden;
use Laminas\Form\Element\Password;
use Laminas\Form\Element\Submit;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\EmailAddress;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\StringLength;

class CompanyUserLogin extends Form implements InputFilterProviderInterface
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
            ],
        );

        $this->add(
            [
                'name' => 'password',
                'type' => Password::class,
                'options' => [
                    'label' => $translator->translate('Password'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $translator->translate('Log in as company'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'redirect',
                'type' => Hidden::class,
            ],
        );

        $this->add(
            [
                'name' => 'security',
                'type' => Csrf::class,
            ],
        );
    }

    /**
     * Set authentication result.
     */
    public function setResult(Result $result): void
    {
        if ($result->isValid()) {
            return;
        }

        $this->isValid = false;

        switch ($result->getCode()) {
            case Result::FAILURE:
                $this->setMessages(
                    [
                        'email' => $result->getMessages(),
                    ],
                );
                break;
            case Result::FAILURE_IDENTITY_NOT_FOUND:
                $this->setMessages(
                    [
                        'email' => $result->getMessages(),
                        'password' => $result->getMessages(),
                    ],
                );
                break;
            case Result::FAILURE_CREDENTIAL_INVALID:
                $this->setMessages(
                    [
                        'password' => $result->getMessages(),
                    ],
                );
                break;
        }
    }

    /**
     * @return array
     */
    public function getInputFilterSpecification(): array
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
                                    'E-mail address format is not valid',
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
