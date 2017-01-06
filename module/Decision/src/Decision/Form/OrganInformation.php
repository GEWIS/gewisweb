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
            'name' => 'thumbnail',
            'type' => 'file',
        ]);

        $this->add([
            'name' => 'cover',
            'type' => 'file',
        ]);

        foreach (['cover', 'thumbnail'] as $type) {
            foreach (['X', 'Y', 'Scale'] as $param) {
                $this->add([
                    'name' => $type . 'Crop' . $param,
                    'type' => 'hidden'
                ]);
            }
        }
        $this->get('thumbnail')->setLabel($translator->translate('Thumbnail photo to upload'));
        $this->get('cover')->setLabel($translator->translate('Cover photo to upload'));

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
            'email' => [
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
            'thumbnail' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => 'File\IsImage',
                    ],
                    [
                        'name' => 'File\Extension',
                        'options' => [
                            'extension' => ['png', 'jpg', 'jpeg', 'bmp', 'tiff']
                        ]
                    ],
                ],
            ],
            'cover' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => 'File\IsImage',
                    ],
                    [
                        'name' => 'File\Extension',
                        'options' => [
                            'extension' => ['png', 'jpg', 'jpeg', 'bmp', 'tiff']
                        ]
                    ],
                ],
            ],
        ];
    }
}
