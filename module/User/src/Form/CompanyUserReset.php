<?php

declare(strict_types=1);

namespace User\Form;

use Laminas\Filter\StringTrim;
use Laminas\Form\Element\Csrf;
use Laminas\Form\Element\Email;
use Laminas\Form\Element\Submit;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\EmailAddress;

/**
 * @psalm-suppress MissingTemplateParam
 */
class CompanyUserReset extends Form implements InputFilterProviderInterface
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
            ],
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $translate->translate('Request password reset'),
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
                                'emailAddressInvalidFormat' => $this->translate->translate(
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
        ];
    }
}
