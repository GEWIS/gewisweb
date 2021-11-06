<?php

namespace Company\Form;

use Application\Form\Localisable as LocalisableForm;
use Laminas\Form\Element\{
    Checkbox,
    Date,
    File,
    Submit,
    Textarea,
};
use Laminas\Validator\StringLength;
use Laminas\Filter\{
    StringTrim,
    StripTags,
};
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\Date as DateValidator;

class Package extends LocalisableForm implements InputFilterProviderInterface
{
    /**
     * @var string
     */
    private string $type;

    public function __construct(Translator $translator, string $type)
    {
        parent::__construct($translator);
        $this->type = $type;
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
