<?php

declare(strict_types=1);

namespace Decision\Form;

use Laminas\Form\Element\{
    File,
    Hidden,
    Submit,
    Text,
};
use Laminas\Form\Form;
use Laminas\Mvc\I18n\Translator;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\{
    File\Extension,
    File\MimeType,
    StringLength,
};

class Document extends Form implements InputFilterProviderInterface
{
    public function __construct(Translator $translator)
    {
        parent::__construct();

        $this->add(
            [
                'name' => 'meeting',
                'type' => Hidden::class,
                'options' => [
                    'label' => $translator->translate('Meeting'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'name',
                'type' => Text::class,
                'options' => [
                    'label' => $translator->translate('Document name'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'upload',
                'type' => File::class,
                'options' => [
                    'label' => $translator->translate('Document to upload'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $translator->translate('Upload document'),
                ],
            ]
        );
    }

    /**
     * Input filter specification.
     *
     * @return array
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'name' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 2,
                            'max' => 128,
                        ],
                    ],
                ],
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
        ];
    }
}
