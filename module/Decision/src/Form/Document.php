<?php

namespace Decision\Form;

use Laminas\Form\Form;
use Laminas\I18n\Translator\TranslatorInterface as Translator;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\File\Extension;
use Laminas\Validator\File\MimeType;
use Laminas\Validator\StringLength;

class Document extends Form implements InputFilterProviderInterface
{
    /**
     * @var Translator
     */
    private $translator;

    public function __construct(Translator $translator)
    {
        parent::__construct();
        $this->translator = $translator;

        $this->add(
            [
                'name' => 'meeting',
                'type' => 'hidden',
                'options' => [
                    'label' => $translator->translate('Meeting'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'name',
                'type' => 'text',
            ]
        );
        $this->get('name')->setLabel($translator->translate('Document name'));

        $this->add(
            [
                'name' => 'upload',
                'type' => 'file',
            ]
        );
        $this->get('upload')->setLabel($translator->translate('Document to upload'));

        $this->add(
            [
                'name' => 'submit',
                'type' => 'submit',
                'attributes' => [
                    'value' => $translator->translate('Upload document'),
                ],
            ]
        );
    }

    /**
     * Input filter specification.
     */
    public function getInputFilterSpecification()
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
