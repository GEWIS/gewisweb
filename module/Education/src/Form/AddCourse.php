<?php

namespace Education\Form;

use Laminas\Filter\StringToUpper;
use Laminas\Form\Element\{
    Select,
    Submit,
    Text,
    Url,
};
use Laminas\Form\Form;
use Laminas\I18n\Validator\Alnum;
use Laminas\Mvc\I18n\Translator;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\StringLength;

class AddCourse extends Form implements InputFilterProviderInterface
{
    public function __construct(Translator $translator)
    {
        $qOptions = [
            'q1' => $translator->translate('Quartile 1'),
            'q2' => $translator->translate('Quartile 2'),
            'q3' => $translator->translate('Quartile 3'),
            'q4' => $translator->translate('Quartile 4'),
            'interim' => $translator->translate('Interim'),
        ];

        parent::__construct();

        $this->add(
            [
                'name' => 'code',
                'type' => Text::class,
                'options' => [
                    'label' => $translator->translate('Course code'),
                ],
            ]
        );
        $this->add(
            [
                'name' => 'parent',
                'type' => Text::class,
                'options' => [
                    'label' => $translator->translate('Parent course code'),
                ],
            ]
        );
        $this->add(
            [
                'name' => 'name',
                'type' => Text::class,
                'options' => [
                    'label' => $translator->translate('Name'),
                ],
            ]
        );
        $this->add(
            [
                'name' => 'url',
                'type' => Url::class,
                'options' => [
                    'label' => $translator->translate('URL'),
                ],
            ]
        );
        $this->add(
            [
                'name' => 'year',
                'type' => Text::class,
                'options' => [
                    'label' => $translator->translate('Year'),
                    'value' => date('Y'),
                ],
            ]
        );
        $this->add(
            [
                'name' => 'quartile',
                'type' => Select::class,
                'options' => [
                    'label' => $translator->translate('Quartile'),
                    'value_options' => $qOptions,
                ],
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $translator->translate('Add course'),
                ],
            ]
        );
    }

    /**
     * @return array
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'code' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 5,
                            'max' => 6,
                        ],
                    ],
                    [
                        'name' => Alnum::class,
                    ],
                ],
                'filters' => [
                    [
                        'name' => StringToUpper::class,
                    ],
                ],
            ],
            'name' => [
                'required' => true,
            ],
            'url' => [
                'required' => false,
            ],
            'quartile' => [
                'required' => true,
            ],
            'year' => [
                'required' => true,
            ],
        ];
    }
}
