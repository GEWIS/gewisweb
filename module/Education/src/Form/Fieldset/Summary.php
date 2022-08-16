<?php

namespace Education\Form\Fieldset;

use Education\Model\Exam as ExamModel;
use Laminas\Filter\StringToUpper;
use Laminas\Form\Element\{
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
    Regex,
    StringLength,
};

class Summary extends Fieldset implements InputFilterProviderInterface
{
    protected array $config;

    public function __construct(Translator $translator)
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
                    'label' => $translator->translate('Course code'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'date',
                'type' => Date::class,
                'options' => [
                    'label' => $translator->translate('Summary date'),
                    'format' => 'Y-m-d',
                ],
            ]
        );

        $this->add(
            [
                'name' => 'author',
                'type' => Text::class,
                'options' => [
                    'label' => $translator->translate('Author'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'language',
                'type' => Select::class,
                'options' => [
                    'label' => $translator->translate('Language'),
                    'value_options' => [
                        ExamModel::EXAM_LANGUAGE_ENGLISH => $translator->translate('English'),
                        ExamModel::EXAM_LANGUAGE_DUTCH => $translator->translate('Dutch'),
                    ],
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
            ],

            'date' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => DateValidator::class,
                    ],
                ],
            ],
        ];
    }
}
