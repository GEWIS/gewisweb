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
use Laminas\InputFilter\InputProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\{
    NotEmpty,
    StringLength,
};

class Login extends Form implements InputProviderInterface
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
                'name' => 'login',
                'type' => Text::class,
                'options' => [
                    'label' => $translate->translate('Membership number or email address'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'password',
                'type' => Password::class,
                'options' => [
                    'label' => $translate->translate('Your password'),
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
                'name' => 'remember',
                'type' => Checkbox::class,
                'options' => [
                    'label' => $translate->translate('Remember me'),
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
            switch ($result->getCode()) {
                case Result::FAILURE_IDENTITY_NOT_FOUND:
                    $this->setMessages(
                        [
                            'login' => [
                                $this->translate->translate('This user could not be found.'),
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
