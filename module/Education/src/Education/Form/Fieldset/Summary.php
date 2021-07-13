<?php

namespace Education\Form\Fieldset;

use Education\Model\Exam as ExamModel;
use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\Callback;
use Laminas\Validator\File\Exists;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;

class Summary extends Fieldset implements InputFilterProviderInterface
{
    protected $config;

    public function __construct(Translator $translator)
    {
        parent::__construct('exam');

        $this->add(
            [
            'name' => 'file',
            'type' => 'hidden',
            ]
        );

        $this->add(
            [
            'name' => 'course',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Course code'),
            ],
            ]
        );

        $this->add(
            [
            'name' => 'date',
            'type' => 'date',
            'options' => [
                'label' => $translator->translate('Summary date'),
            ],
            ]
        );

        $this->add(
            [
            'name' => 'author',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Author'),
            ],
            ]
        );

        $this->add(
            [
            'type' => 'Laminas\Form\Element\Select',
            'name' => 'language',
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
    public function setConfig($config)
    {
        $this->config = $config['education_temp'];
    }

    public function getInputFilterSpecification()
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
                    ['name' => 'alnum'],
                ],
                'filters' => [
                    ['name' => 'string_to_upper'],
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
                    ['name' => 'date'],
                ],
            ],
        ];
    }
}
