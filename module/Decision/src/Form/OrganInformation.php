<?php

declare(strict_types=1);

namespace Decision\Form;

use Laminas\Form\Element\Email;
use Laminas\Form\Element\File;
use Laminas\Form\Element\Hidden;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\Form\Element\Textarea;
use Laminas\Form\Element\Url;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\File\Extension;
use Laminas\Validator\File\IsImage;
use Laminas\Validator\StringLength;

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
            ],
        );

        $this->add(
            [
                'name' => 'website',
                'type' => Url::class,
                'options' => [
                    'label' => $translator->translate('Website'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'tagline',
                'type' => Text::class,
                'options' => [
                    'label' => $translator->translate('Tagline'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'taglineEn',
                'type' => Text::class,
                'options' => [
                    'label' => $translator->translate('Tagline'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'description',
                'type' => Textarea::class,
                'options' => [
                    'label' => $translator->translate('Description'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'descriptionEn',
                'type' => Textarea::class,
                'options' => [
                    'label' => $translator->translate('Description'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'thumbnail',
                'type' => File::class,
                'options' => [
                    'label' => $translator->translate('Thumbnail photo to upload'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'cover',
                'type' => File::class,
                'options' => [
                    'label' => $translator->translate('Cover photo to upload'),
                ],
            ],
        );

        foreach (['cover', 'thumbnail'] as $type) {
            foreach (['X', 'Y', 'Width', 'Height'] as $param) {
                $this->add(
                    [
                        'name' => $type . 'Crop' . $param,
                        'type' => Hidden::class,
                    ],
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
            ],
        );
    }

    /**
     * Should return an array specification compatible with
     * {@link \Laminas\InputFilter\Factory::createInputFilter()}.
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
            'tagline' => [
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
            'taglineEn' => [
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
            'description' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'max' => 10000,
                        ],
                    ],
                ],
            ],
            'descriptionEn' => [
                'required' => true,
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
