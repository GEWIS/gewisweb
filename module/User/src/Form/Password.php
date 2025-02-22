<?php

declare(strict_types=1);

namespace User\Form;

use Laminas\Form\Element\Password as PasswordElement;
use Laminas\Form\Element\Submit;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\Identical;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\StringLength;

/**
 * @psalm-suppress MissingTemplateParam
 */
class Password extends Form implements InputFilterProviderInterface
{
    public function __construct(
        private readonly Translator $translator,
        private readonly int $passwordLength,
    ) {
        parent::__construct();

        $this->add(
            [
                'name' => 'old_password',
                'type' => PasswordElement::class,
                'options' => [
                    'label' => $this->translator->translate('Old password'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'password',
                'type' => PasswordElement::class,
                'options' => [
                    'label' => $this->translator->translate('New password'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'password_verify',
                'type' => PasswordElement::class,
                'options' => [
                    'label' => $this->translator->translate('Verify new password'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $this->translator->translate('Change password'),
                ],
            ],
        );
    }

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
