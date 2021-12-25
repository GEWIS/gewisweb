<?php

namespace Frontpage\Form;

use Laminas\Filter\{
    StringToLower,
    ToNull,
};
use Laminas\Form\Element\{
    Submit,
    Text,
    Textarea,
};
use Laminas\Form\Form;
use Laminas\Mvc\I18n\Translator;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\StringLength;

class Page extends Form implements InputFilterProviderInterface
{
    /**
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
    {
        parent::__construct();

        $this->add(
            [
                'name' => 'category',
                'type' => Text::class,
            ]
        );

        $this->add(
            [
                'name' => 'subCategory',
                'type' => Text::class,
            ]
        );

        $this->add(
            [
                'name' => 'name',
                'type' => Text::class,
            ]
        );

        $this->add(
            [
                'name' => 'dutchTitle',
                'type' => Text::class,
                'options' => [
                    'label' => $translator->translate('Dutch title'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'englishTitle',
                'type' => Text::class,
                'options' => [
                    'label' => $translator->translate('English title'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'dutchContent',
                'type' => Textarea::class,
                'options' => [
                    'label' => $translator->translate('Dutch content'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'englishContent',
                'type' => Textarea::class,
                'options' => [
                    'label' => $translator->translate('English content'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'requiredRole',
                'type' => Text::class,
                'options' => [
                    'label' => $translator->translate('Required role'),
                    'value' => 'guest',
                ],
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
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
    public function getInputFilterSpecification(): array
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
                    [
                        'name' => StringToLower::class,
                    ],
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
                    [
                        'name' => StringToLower::class,
                    ],
                    [
                        'name' => ToNull::class,
                    ],
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
                    [
                        'name' => StringToLower::class,
                    ],
                    [
                        'name' => ToNull::class,
                    ],
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
