<?php

declare(strict_types=1);

namespace User\Form;

use Laminas\Form\Element\Csrf;
use Laminas\Form\Element\Number;
use Laminas\Form\Element\Submit;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\Digits;
use Laminas\Validator\NotEmpty;

/**
 * @psalm-suppress MissingTemplateParam
 */
class Register extends Form implements InputFilterProviderInterface
{
    public const string ERROR_NO_EMAIL = 'no_email';
    public const string ERROR_MEMBER_NOT_EXISTS = 'member_not_exists';
    public const string ERROR_USER_ALREADY_EXISTS = 'user_already_exists';
    public const string ERROR_ALREADY_REGISTERED = 'already_registered';

    public function __construct(protected Translator $translator)
    {
        parent::__construct();

        $this->add(
            [
                'name' => 'lidnr',
                'type' => Number::class,
                'options' => [
                    'label' => $translator->translate('Membership number'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $translator->translate('Request Activation'),
                ],
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
     * Set the error.
     */
    public function setError(string $error): void
    {
        switch ($error) {
            case self::ERROR_NO_EMAIL:
                $this->setMessages([
                    'lidnr' => [
                        $this->translator->translate(
                            'This member cannot create an account, please contact the secretary for more information.',
                        ),
                    ],
                ]);
                break;
            case self::ERROR_MEMBER_NOT_EXISTS:
                $this->setMessages([
                    'lidnr' => [
                        $this->translator->translate('There is no member with this membership number.'),
                    ],
                ]);
                break;
            case self::ERROR_ALREADY_REGISTERED:
                $this->setMessages([
                    'lidnr' => [
                        $this->translator->translate(
                            'You already attempted to register, please check your email or try again after 20 minutes.',
                        ),
                    ],
                ]);
                break;
            case self::ERROR_USER_ALREADY_EXISTS:
                $this->setMessages([
                    'lidnr' => [
                        $this->translator->translate('This member already has an account.'),
                    ],
                ]);
                break;
        }
    }

    public function getInputFilterSpecification(): array
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
        ];
    }
}
