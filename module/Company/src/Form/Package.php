<?php

namespace Company\Form;

use Laminas\Form\Element\{
    Checkbox,
    Date,
    File,
    Radio,
    Submit,
    Textarea,
};
use Laminas\Filter\{
    StringTrim,
    StripTags,
};
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\Date as DateValidator;

class Package extends Form implements InputFilterProviderInterface
{
    public function __construct(Translator $translator, string $type)
    {
        // we want to ignore the name passed
        parent::__construct();

        $this->setAttribute('method', 'post');

        $this->add(
            [
                'name' => 'startDate',
                'type' => Date::class,
                'attributes' => [
                    'step' => '1',
                ],
            ]
        );

        $this->add(
            [
                'name' => 'expirationDate',
                'type' => Date::class,
                'attributes' => [
                    'step' => '1',
                ],
            ]
        );

        $this->add(
            [
                'name' => 'published',
                'type' => Checkbox::class,
                'options' => [
                    'value_options' => [
                        '0' => 'Enabled',
                    ],
                ],
            ]
        );

        if ('featured' === $type) {
            $this->add(
                [
                    'name' => 'article',
                    'type' => Textarea::class,
                ]
            );

            $this->add(
                [
                    'name' => 'language',
                    'type' => Radio::class,
                    'options' => [
                        'label' => 'Language',
                        'value_options' => [
                            'nl' => $translator->translate('Dutch'),
                            'en' => $translator->translate('English'),
                        ],
                    ],
                ]
            );
        }

        if ('banner' === $type) {
            $this->add(
                [
                    'name' => 'banner',
                    'type' => File::class,
                ]
            );
        }

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
            ]
        );
    }

    /**
     * @return array
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'startDate' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => DateValidator::class,
                    ],
                ],
                'filters' => [
                    [
                        'name' => StripTags::class,
                    ],
                    [
                        'name' => StringTrim::class,
                    ],
                ],
            ],
            'expirationDate' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => DateValidator::class,
                    ],
                ],
                'filters' => [
                    [
                        'name' => StripTags::class,
                    ],
                    [
                        'name' => StringTrim::class,
                    ],
                ],
            ],
        ];
    }
}
