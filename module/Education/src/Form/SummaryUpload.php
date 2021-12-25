<?php

namespace Education\Form;

use Education\Model\Exam as ExamModel;
use Laminas\Filter\StringToUpper;
use Laminas\Form\Element\{
    Date,
    File,
    Select,
    Submit,
    Text,
};
use Laminas\Form\Form;
use Laminas\I18n\Validator\Alnum;
use Laminas\Mvc\I18n\Translator;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\{
    File\Extension,
    File\MimeType,
    Regex,
    StringLength,
};

/**
 * Upload a summary.
 */
class SummaryUpload extends Form implements InputFilterProviderInterface
{
    /**
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
    {
        parent::__construct();

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

        $this->add(
            [
                'name' => 'upload',
                'type' => File::class,
                'options' => [
                    'label' => $translator->translate('Summary to upload'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $translator->translate('Submit'),
                ],
            ]
        );
    }

    /**
     * @return array
     */
    public function getInputFilterSpecification(): array
    {
        return [
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

            'date' => [
                'required' => true,
            ],

            'upload' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Extension::class,
                        'options' => [
                            'extension' => 'pdf',
                        ],
                    ],
                    [
                        'name' => MimeType::class,
                        'options' => [
                            'mimeType' => 'application/pdf',
                        ],
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
                            'max' => 64,
                        ],
                    ],
                    [
                        'name' => Regex::class,
                        'options' => [
                            'pattern' => '/[a-zA-Z ]+/',
                        ],
                    ],
                ],
            ],
        ];
    }
}
