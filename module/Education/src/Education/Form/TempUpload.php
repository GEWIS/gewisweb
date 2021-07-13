<?php

namespace Education\Form;

use Laminas\Form\Form;
use Laminas\I18n\Translator\TranslatorInterface as Translator;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\File\Extension;
use Laminas\Validator\File\MimeType;

class TempUpload extends Form implements InputFilterProviderInterface
{
    public function __construct(Translator $translator)
    {
        parent::__construct();

        $this->add(
            [
            'name' => 'file',
            'type' => 'file',
            'option' => [
                'label' => $translator->translate('Exam to upload'),
            ],
            ]
        );
        $this->get('file')->setLabel($translator->translate('Exam to upload'));
    }

    public function getInputFilterSpecification()
    {
        return [
            'file' => [
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
