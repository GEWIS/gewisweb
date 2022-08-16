<?php

namespace Decision\Form;

use Laminas\Form\Element\{
    Email,
    File,
    Hidden,
    Submit,
    Text,
    Textarea,
    Url,
};
use Laminas\Form\Form;
use Laminas\Mvc\I18n\Translator;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\{
    File\Extension,
    File\IsImage,
    StringLength,
};

class OrganInformation extends Form implements InputFilterProviderInterface
{
    public function __construct(Translator $translator)
    {
        parent::__construct();

        $this->add(
            [
                'name' => 'email',
                'type' => Email::class,
                'options' => [
                    'label' => $translator->translate('Email'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'website',
                'type' => Url::class,
                'options' => [
                    'label' => $translator->translate('Website'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'shortDutchDescription',
                'type' => Text::class,
                'options' => [
                    'label' => $translator->translate('Short dutch description'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'shortEnglishDescription',
                'type' => Text::class,
                'options' => [
                    'label' => $translator->translate('Short english description'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'dutchDescription',
                'type' => Textarea::class,
                'options' => [
                    'label' => $translator->translate('Long dutch description'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'englishDescription',
                'type' => Textarea::class,
                'options' => [
                    'label' => $translator->translate('Long english description'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'thumbnail',
                'type' => File::class,
                'options' => [
                    'label' => $translator->translate('Thumbnail photo to upload'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'cover',
                'type' => File::class,
                'options' => [
                    'label' => $translator->translate('Cover photo to upload'),
                ],
            ]
        );

        foreach (['cover', 'thumbnail'] as $type) {
            foreach (['X', 'Y', 'Width', 'Height'] as $param) {
                $this->add(
                    [
                        'name' => $type . 'Crop' . $param,
                        'type' => Hidden::class,
                    ]
                );
            }
        }

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $translator->translate('Save'),
                ],
            ]
        );
    }

    /**
     * Should return an array specification compatible with
     * {@link \Laminas\InputFilter\Factory::createInputFilter()}.
     *
     * @return array
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'website' => [
                'required' => false,
            ],
            'email' => [
                'required' => false,
            ],
            'shortDutchDescription' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'max' => 150,
                        ],
                    ],
                ],
            ],
            'shortEnglishDescription' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'max' => 150,
                        ],
                    ],
                ],
            ],
            'dutchDescription' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'max' => 10000,
                        ],
                    ],
                ],
            ],
            'englishDescription' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'max' => 10000,
                        ],
                    ],
                ],
            ],
            'thumbnail' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => IsImage::class,
                    ],
                    [
                        'name' => Extension::class,
                        'options' => [
                            'extension' => ['png', 'jpg', 'jpeg', 'tiff', 'gif'],
                        ],
                    ],
                ],
            ],
            'cover' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => IsImage::class,
                    ],
                    [
                        'name' => Extension::class,
                        'options' => [
                            'extension' => ['png', 'jpg', 'jpeg', 'tiff', 'gif'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
