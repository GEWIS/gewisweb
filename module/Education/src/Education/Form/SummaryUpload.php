<?php

namespace Education\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\I18n\Translator\TranslatorInterface as Translator;
use Education\Model\Exam as ExamModel;
use Zend\Validator\Regex;
use Zend\Validator\StringLength;

/**
 * Upload a summary
 */
class SummaryUpload extends Form implements InputFilterProviderInterface
{
    public function __construct(Translator $translator)
    {
        parent::__construct();

        $this->add([
            'name' => 'course',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Course code')
            ]
        ]);

        $this->add([
            'name' => 'date',
            'type' => 'date',
            'options' => [
                'label' => $translator->translate('Summary date')
            ]
        ]);

        $this->add([
            'name' => 'author',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Author')
            ]
        ]);

        $this->add([
            'type' => 'Zend\Form\Element\Select',
            'name' => 'language',
            'options' => [
                'label' => $translator->translate('Language'),
                'value_options' => [
                    ExamModel::EXAM_LANGUAGE_ENGLISH => $translator->translate('English'),
                    ExamModel::EXAM_LANGUAGE_DUTCH => $translator->translate('Dutch'),
                ],
            ],
        ]);

        $this->add([
            'name' => 'upload',
            'type' => 'file',
            'option' => [
                'label' => $translator->translate('Summary to upload')
            ]
        ]);
        $this->get('upload')->setLabel($translator->translate('Summary to upload'));

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => $translator->translate('Submit')
            ]
        ]);
    }

    public function getInputFilterSpecification()
    {
        return [
            'course' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 5,
                            'max' => 6
                        ]
                    ],
                    ['name' => 'alnum']
                ],
                'filters' => [
                    ['name' => 'string_to_upper']
                ]
            ],

            'date' => [
                'required' => true
            ],

            'upload' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'File\Extension',
                        'options' => [
                            'extension' => 'pdf'
                        ]
                    ],
                    [
                        'name' => 'File\MimeType',
                        'options' => [
                            'mimeType' => 'application/pdf'
                        ]
                    ]
                ]
            ],

            'author' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 3,
                            'max' => 64
                        ]
                    ],
                    [
                        'name' => Regex::class,
                        'options' => [
                            'pattern' => '/[a-zA-Z ]+/'
                        ]
                    ]
                ]
            ]
        ];
    }
}
