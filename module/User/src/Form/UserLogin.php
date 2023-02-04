<?php

namespace User\Form;

use Laminas\Authentication\Result;
use Laminas\Form\Element\{
    Checkbox,
    Csrf,
    Hidden,
    Password,
    Submit,
    Text,
};
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\{
    NotEmpty,
    StringLength,
};

class UserLogin extends Form implements InputFilterProviderInterface
{
    public function __construct(private readonly Translator $translator)
    {
        parent::__construct();

        $this->add(
            [
                'name' => 'login',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Membership number or email address'),
                ],
            ]
        );

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
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $this->translator->translate('Log in as member'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'remember',
                'type' => Checkbox::class,
                'options' => [
                    'label' => $this->translator->translate('Remember me'),
                    'checked_value' => '1',
                    'unchecked_value' => '0',
                    'checked' => true,
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
            $this->isValid = false;

            switch ($result->getCode()) {
                case Result::FAILURE_UNCATEGORIZED:
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
    public function getInputFilterSpecification(): array
    {
        return [
            'login' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => NotEmpty::class,
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
