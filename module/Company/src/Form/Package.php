<?php

declare(strict_types=1);

namespace Company\Form;

use Application\Form\Localisable as LocalisableForm;
use Laminas\Form\Element\{
    Checkbox,
    Date,
    File,
    Submit,
    Text,
    Textarea,
};
use Laminas\Validator\{
    Date as DateValidator,
    Regex,
    StringLength};
use Laminas\I18n\Validator\Alnum;
use Laminas\Filter\{
    StringTrim,
    StripTags,
    ToNull,
};
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;

class Package extends LocalisableForm implements InputFilterProviderInterface
{
    public function __construct(
        Translator $translator,
        private readonly string $type,
    ) {
        parent::__construct($translator, ('featured' === $type));
        $this->setAttribute('method', 'post');

        $this->add(
            [
                'name' => 'startDate',
                'type' => Date::class,
                'options' => [
                    'label' => $translator->translate('Start Date'),
                    'format' => 'Y-m-d',
                ],
                'attributes' => [
                    'step' => '1',
                ],
            ]
        );

        $this->add(
            [
                'name' => 'expirationDate',
                'type' => Date::class,
                'options' => [
                    'label' => $translator->translate('Expiration Date'),
                    'format' => 'Y-m-d',
                ],
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
                    'label' => $translator->translate('Published'),
                    'value_options' => [
                        '0' => 'Enabled',
                    ],
                ],
            ]
        );

        $this->add(
            [
                'name' => 'contractNumber',
                'type' => Text::class,
                'options' => [
                    'label' => $translator->translate('Contract Number'),
                ],
            ]
        );

        if ('featured' === $type) {
            $this->add(
                [
                    'name' => 'article',
                    'type' => Textarea::class,
                    'options' => [
                        'label' => $translator->translate('Article'),
                    ],
                ]
            );

            $this->add(
                [
                    'name' => 'articleEn',
                    'type' => Textarea::class,
                    'options' => [
                        'label' => $translator->translate('Article'),
                    ],
                ]
            );
        }

        if ('banner' === $type) {
            $this->add(
                [
                    'name' => 'banner',
                    'type' => File::class,
                    'options' => [
                        'label' => $translator->translate('Banner'),
                    ],
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
        $filter = [];

        if ('featured' === $this->type) {
            $filter = parent::getInputFilterSpecification();
        }

        $filter += [
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
            'contractNumber' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Regex::class,
                        'options' => [
                            'pattern' => '/^[0-9a-zA-Z_\-\.\s]+$/',
                            'messages' => [
                                Regex::ERROROUS => $this->getTranslator()->translate(
                                    'Contract numbers can only contain letters, numbers, _, -, ., and spaces'
                                ),
                            ],
                        ],
                    ],
                ],
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                    [
                        'name' => ToNull::class,
                    ],
                ],
            ],
        ];

        return $filter;
    }

    /**
     * @inheritDoc
     */
    protected function createLocalisedInputFilterSpecification(string $suffix = ''): array
    {
        return [
            'article' . $suffix => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 2,
                            'max' => 10000,
                        ],
                    ],
                ],
            ],
        ];
    }
}
