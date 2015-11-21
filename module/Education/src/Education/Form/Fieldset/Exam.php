<?php

namespace Education\Form\Fieldset;

use Zend\Form\Fieldset;
use Zend\InputFilter\InputFilterProviderInterface;
use zend\I18n\Translator\TranslatorInterface as Translator;

class Exam extends Fieldset
    implements InputFilterProviderInterface
{

    protected $config;

    public function __construct(Translator $translator)
    {
        parent::__construct('exam');

        $this->add([
            'name' => 'file',
            'type' => 'hidden'
        ]);

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
                'label' => $translator->translate('Exam date')
            ]
        ]);
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
        $dir = $this->config['upload_dir'];
        return [
            'file' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'regex',
                        'options' => [
                            'pattern' => '/^[a-zA-Z0-9_ ,.-]+\.pdf$/'
                        ]
                    ],
                    [
                        'name' => 'callback',
                        'options' => [
                            'callback' => function ($value) use ($dir) {
                                $validator = new \Zend\Validator\File\Exists([
                                    'directory' => $dir
                                ]);
                                return $validator->isValid($value);
                            }
                        ]
                    ]
                ]
            ],

            'course' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'string_length',
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
                'required' => true,
                'validators' => [
                    ['name' => 'date']
                ]
            ]
        ];
    }

}
