<?php

namespace Education\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\I18n\Translator\TranslatorInterface as Translator;

class Upload extends Form
{

    public function __construct(Translator $translator)
    {
        parent::__construct();

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

        $this->add([
            'name' => 'upload',
            'type' => 'file',
            'option' => [
                'label' => $translator->translate('Exam to upload')
            ]
        ]);
        $this->get('upload')->setLabel($translator->translate('Exam to upload'));

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => $translator->translate('Submit')
            ]
        ]);

        $this->initFilters();
    }

    protected function initFilters()
    {
        $filter = new InputFilter();


        $filter->add([
            'name' => 'course',
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
        ]);

        $filter->add([
            'name' => 'date',
            'required' => true,
            'validators' => [
                ['name' => 'date']
            ]
        ]);

        $filter->add([
            'name' => 'upload',
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
        ]);

        $this->setInputFilter($filter);
    }
}
