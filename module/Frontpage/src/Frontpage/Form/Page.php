<?php

namespace Frontpage\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\I18n\Translator\TranslatorInterface as Translator;

class Page extends Form implements InputFilterProviderInterface
{

    public function __construct(Translator $translator)
    {
        parent::__construct();

        $this->add([
            'name' => 'category',
            'type' => 'text',
        ]);

        $this->add([
            'name' => 'subCategory',
            'type' => 'text',
        ]);

        $this->add([
            'name' => 'name',
            'type' => 'text',
        ]);

        $this->add([
            'name' => 'dutchTitle',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Dutch title')
            ]
        ]);

        $this->add([
            'name' => 'englishTitle',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('English title')
            ]
        ]);

        $this->add([
            'name' => 'dutchContent',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Dutch content')
            ]
        ]);

        $this->add([
            'name' => 'englishContent',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('English content')
            ]
        ]);

        $this->add([
            'name' => 'requiredRole',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Required role'),
                'value' => 'guest'
            ]
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => $translator->translate('Save')
            ]
        ]);
    }

    /**
     * Should return an array specification compatible with
     * {@link Zend\InputFilter\Factory::createInputFilter()}.
     *
     * @return array
     */
    public function getInputFilterSpecification()
    {
        return [
            'category' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'string_length',
                        'options' => [
                            'min' => 3,
                            'max' => 25
                        ]
                    ],
                ],
                'filters' => [
                    ['name' => 'string_to_lower']
                ]
            ],

            'subCategory' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => 'string_length',
                        'options' => [
                            'min' => 2,
                            'max' => 25
                        ]
                    ],
                ],
                'filters' => [
                    ['name' => 'string_to_lower'],
                    ['name' => 'to_null']
                ]
            ],

            'name' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => 'string_length',
                        'options' => [
                            'min' => 2,
                            'max' => 25
                        ]
                    ],
                ],
                'filters' => [
                    ['name' => 'string_to_lower'],
                    ['name' => 'to_null']
                ]
            ],

            'dutchTitle' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'string_length',
                        'options' => [
                            'min' => 3,
                            'max' => 64
                        ]
                    ],
                ],
            ],

            'englishTitle' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'string_length',
                        'options' => [
                            'min' => 3,
                            'max' => 64
                        ]
                    ],
                ],
            ],

            'dutchContent' => [
                'required' => true,
            ],

            'englishContent' => [
                'required' => true,
            ],

            'requiredRole' => [
                'required' => true,
            ],
        ];
    }
}
