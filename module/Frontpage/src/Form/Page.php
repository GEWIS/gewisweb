<?php

namespace Frontpage\Form;

use Laminas\Form\Form;
use Laminas\I18n\Translator\TranslatorInterface as Translator;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\StringLength;

class Page extends Form implements InputFilterProviderInterface
{
    public function __construct(Translator $translator)
    {
        parent::__construct();

        $this->add(
            [
                'name' => 'category',
                'type' => 'text',
            ]
        );

        $this->add(
            [
                'name' => 'subCategory',
                'type' => 'text',
            ]
        );

        $this->add(
            [
                'name' => 'name',
                'type' => 'text',
            ]
        );

        $this->add(
            [
                'name' => 'dutchTitle',
                'type' => 'text',
                'options' => [
                    'label' => $translator->translate('Dutch title'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'englishTitle',
                'type' => 'text',
                'options' => [
                    'label' => $translator->translate('English title'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'dutchContent',
                'type' => 'text',
                'options' => [
                    'label' => $translator->translate('Dutch content'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'englishContent',
                'type' => 'text',
                'options' => [
                    'label' => $translator->translate('English content'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'requiredRole',
                'type' => 'text',
                'options' => [
                    'label' => $translator->translate('Required role'),
                    'value' => 'guest',
                ],
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => 'submit',
                'attributes' => [
                    'value' => $translator->translate('Save'),
                ],
            ]
        );
    }

    /**
     * Should return an array specification compatible with
     * {@link \Laminas\InputFilter\Factory::createInputFilter()}.
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
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 3,
                            'max' => 25,
                        ],
                    ],
                ],
                'filters' => [
                    ['name' => 'string_to_lower'],
                ],
            ],

            'subCategory' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 2,
                            'max' => 25,
                        ],
                    ],
                ],
                'filters' => [
                    ['name' => 'string_to_lower'],
                    ['name' => 'to_null'],
                ],
            ],

            'name' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 2,
                            'max' => 25,
                        ],
                    ],
                ],
                'filters' => [
                    ['name' => 'string_to_lower'],
                    ['name' => 'to_null'],
                ],
            ],

            'dutchTitle' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 3,
                            'max' => 64,
                        ],
                    ],
                ],
            ],

            'englishTitle' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 3,
                            'max' => 64,
                        ],
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
