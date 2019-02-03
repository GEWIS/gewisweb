<?php

namespace Education\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\I18n\Translator\TranslatorInterface as Translator;

class AddCourse extends Form implements InputFilterProviderInterface
{

    public function __construct(Translator $translator)
    {

        $qOptions = [
            'q1' => $translator->translate('Quartile 1'),
            'q2' => $translator->translate('Quartile 2'),
            'q3' => $translator->translate('Quartile 3'),
            'q4' => $translator->translate('Quartile 4'),
            'interim' => $translator->translate('Interim')
        ];

        parent::__construct();

        $this->add([
            'name' => 'code',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Course code')
            ]
        ]);
        $this->add([
            'name' => 'parent',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Parent course code')
            ]
        ]);
        $this->add([
            'name' => 'name',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Name')
            ]
        ]);
        $this->add([
            'name' => 'url',
            'type' => 'Zend\Form\Element\Url',
            'options' => [
                'label' => $translator->translate('URL')
            ]
        ]);
        $this->add([
            'name' => 'year',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Year'),
                'value' => date("Y")
            ]
        ]);
        $this->add([
            'name' => 'quartile',
            'type' => 'select',
            'options' => [
                'label' => $translator->translate('Quartile'),
                'value_options' => $qOptions
            ]
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit'
        ]);

        $this->get('submit')->setLabel($translator->translate('Add course'));
    }

    public function getInputFilterSpecification()
    {
        return [
            'code' => [
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
            ], 'name' => [
                'required' => true
            ], 'url' => [
                'required' => false
            ], 'quartile' => [
                'required' => true
            ], 'year' => [
                'required' => true
            ]
        ];
    }
}
