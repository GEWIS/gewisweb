<?php

declare(strict_types=1);

namespace Company\Form;

use Application\Form\Localisable as LocalisableForm;
use Laminas\Filter\StringTrim;
use Laminas\Filter\StripTags;
use Laminas\Filter\ToNull;
use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\Date;
use Laminas\Form\Element\File;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\Form\Element\Textarea;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\Date as DateValidator;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;
use Override;

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
            ],
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
            ],
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
            ],
        );

        $this->add(
            [
                'name' => 'contractNumber',
                'type' => Text::class,
                'options' => [
                    'label' => $translator->translate('Contract Number'),
                ],
            ],
        );

        if ('featured' === $type) {
            $this->add(
                [
                    'name' => 'article',
                    'type' => Textarea::class,
                    'options' => [
                        'label' => $translator->translate('Article'),
                    ],
                ],
            );

            $this->add(
                [
                    'name' => 'articleEn',
                    'type' => Textarea::class,
                    'options' => [
                        'label' => $translator->translate('Article'),
                    ],
                ],
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
                ],
            );
        }

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
            ],
        );
    }

    #[Override]
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
                                Regex::NOT_MATCH => $this->getTranslator()->translate(
                                    'Contract numbers can only contain letters, numbers, _, -, ., and spaces',
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
    #[Override]
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
