<?php

declare(strict_types=1);

namespace Education\Form\Fieldset;

use Application\Model\Enums\Languages;
use Laminas\Filter\{
    StringToUpper,
    ToNull,
};
use Laminas\Form\Element\{
    Checkbox,
    Date,
    Hidden,
    Select,
    Text,
};
use Laminas\Form\Fieldset;
use Laminas\I18n\Validator\Alnum;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\{
    Callback,
    Date as DateValidator,
    File\Exists,
    InArray,
    Regex,
    StringLength,
};

class Summary extends Fieldset implements InputFilterProviderInterface
{
    protected array $config;

    public function __construct(private readonly Translator $translator)
    {
        parent::__construct('exam');

        $this->add(
            [
                'name' => 'file',
                'type' => Hidden::class,
            ]
        );

        $this->add(
            [
                'name' => 'course',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Course code'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'date',
                'type' => Date::class,
                'options' => [
                    'label' => $this->translator->translate('Summary date'),
                    'format' => 'Y-m-d',
                ],
            ]
        );

        $this->add(
            [
                'name' => 'author',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Author'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'language',
                'type' => Select::class,
                'options' => [
                    'label' => $this->translator->translate('Language'),
                    'value_options' => [
                        Languages::EN->value => Languages::EN->getName($this->translator),
                        Languages::NL->value => Languages::NL->getName($this->translator),
                    ],
                ],
            ]
        );

        $this->add(
            [
                'name' => 'scanned',
                'type' => Checkbox::class,
                'options' => [
                    'label' => $this->translator->translate('Scanned?'),
                ],
            ]
        );
    }

    /**
     * Set the configuration.
     *
     * @param array $config
     */
    public function setConfig(array $config): void
    {
        $this->config = $config['education_temp'];
    }

    /**
     * @return array
     */
    public function getInputFilterSpecification(): array
    {
        $dir = $this->config['upload_summary_dir'];

        return [
            'file' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Regex::class,
                        'options' => [
                            'pattern' => '/.+\.pdf$/',
                        ],
                    ],
                    [
                        'name' => Callback::class,
                        'options' => [
                            'callback' => function ($value) use ($dir) {
                                $validator = new Exists(
                                    [
                                        'directory' => $dir,
                                    ]
                                );

                                return $validator->isValid($value);
                            },
                        ],
                    ],
                ],
            ],
            'course' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 5,
                            'max' => 6,
                        ],
                    ],
                    [
                        'name' => Alnum::class,
                    ],
                ],
                'filters' => [
                    [
                        'name' => StringToUpper::class,
                    ],
                ],
            ],
            'author' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 3,
                            'max' => 150,
                        ],
                    ],
                ],
                'filters' => [
                    [
                        'name' => ToNull::class,
                    ],
                ],
            ],
            'date' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => DateValidator::class,
                    ],
                ],
            ],
            'language' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => InArray::class,
                        'options' => [
                            'haystack' => Languages::values(),
                        ],
                    ],
                ],
            ],
        ];
    }
}
