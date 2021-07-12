<?php

namespace Decision\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\I18n\Translator\TranslatorInterface as Translator;
use Zend\Validator\File\Extension;
use Zend\Validator\File\MimeType;
use Zend\Validator\StringLength;

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
            ]
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
                'value' => $translator->translate('Upload document')
            ]
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
                            'max' => 128
                        ]
                    ]
                ]
            ],
            'upload' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Extension::class,
                        'options' => [
                            'extension' => 'pdf'
                        ]
                    ],
                    [
                        'name' => MimeType::class,
                        'options' => [
                            'mimeType' => 'application/pdf'
                        ]
                    ]
                ]
            ]
        ];
    }
}
