<?php

namespace Decision\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\I18n\Translator\TranslatorInterface as Translator;

class OrganInformation extends Form implements InputFilterProviderInterface
{

    public function __construct(Translator $translator)
    {
        parent::__construct();

        $this->add([
            'name' => 'email',
            'type' => 'email',
            'options' => [
                'label' => $translator->translate('Email')
            ]
        ]);

        $this->add([
            'name' => 'website',
            'type' => 'url',
            'options' => [
                'label' => $translator->translate('Website')
            ]
        ]);

        $this->add([
            'name' => 'shortDutchDescription',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Short dutch description')
            ]
        ]);

        $this->add([
            'name' => 'shortEnglishDescription',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Short english description')
            ]
        ]);

        $this->add([
            'name' => 'dutchDescription',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Long dutch description')
            ]
        ]);

        $this->add([
            'name' => 'englishDescription',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Long english description')
            ]
        ]);

        $this->add([
            'name' => 'upload',
            'type' => 'file',
            'option' => [
                'label' => $translator->translate('Cover photo to upload')
            ]
        ]);
        $this->get('upload')->setLabel($translator->translate('Cover photo to upload'));

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => $translator->translate('Save')
            ]
        ]);
    }

    /**
     * Should return an array specification compatible with
     * {@link Zend\InputFilter\Factory::createInputFilter()}.
     *
     * @return array
     */
    public function getInputFilterSpecification()
    {
        return [
            'website' => [
                'required' => false
            ],
            'shortDutchDescription' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => 'string_length',
                        'options' => [
                            'max' => 150
                        ]
                    ],
                ],
            ],
            'shortEnglishDescription' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => 'string_length',
                        'options' => [
                            'max' => 150
                        ]
                    ],
                ],
            ],
            'dutchDescription' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => 'string_length',
                        'options' => [
                            'max' => 10000
                        ]
                    ],
                ],
            ],
            'englishDescription' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => 'string_length',
                        'options' => [
                            'max' => 10000
                        ]
                    ],
                ],
            ],
            'upload' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => 'File\MimeType',
                        'options' => [
                            'mimeType' => 'image/png'
                        ]
                    ],
                    [
                        'name' => 'File\Extension',
                        'options' => [
                            'extension' => 'png'
                        ]
                    ],
                ],
            ],
        ];
    }
}
