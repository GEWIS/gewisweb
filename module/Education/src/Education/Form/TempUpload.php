<?php

namespace Education\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\I18n\Translator\TranslatorInterface as Translator;

class TempUpload extends Form implements InputFilterProviderInterface
{
    public function __construct(Translator $translator)
    {
        parent::__construct();

        $this->add([
            'name' => 'file',
            'type' => 'file',
            'option' => [
                'label' => $translator->translate('Exam to upload')
            ]
        ]);
        $this->get('file')->setLabel($translator->translate('Exam to upload'));
    }

    public function getInputFilterSpecification()
    {
        return [
            'file' => [
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
            ]
        ];
    }
}
