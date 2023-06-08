<?php

declare(strict_types=1);

namespace Frontpage\Form;

use Laminas\Filter\StringToLower;
use Laminas\Filter\ToNull;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\Form\Element\Textarea;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\StringLength;

class Page extends Form implements InputFilterProviderInterface
{
    public function __construct(Translator $translator)
    {
        parent::__construct();

        $this->add(
            [
                'name' => 'category',
                'type' => Text::class,
            ],
        );

        $this->add(
            [
                'name' => 'subCategory',
                'type' => Text::class,
            ],
        );

        $this->add(
            [
                'name' => 'name',
                'type' => Text::class,
            ],
        );

        $this->add(
            [
                'name' => 'dutchTitle',
                'type' => Text::class,
                'options' => [
                    'label' => $translator->translate('Dutch title'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'englishTitle',
                'type' => Text::class,
                'options' => [
                    'label' => $translator->translate('English title'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'dutchContent',
                'type' => Textarea::class,
                'options' => [
                    'label' => $translator->translate('Dutch content'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'englishContent',
                'type' => Textarea::class,
                'options' => [
                    'label' => $translator->translate('English content'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'requiredRole',
                'type' => Text::class,
                'options' => [
                    'label' => $translator->translate('Required role'),
                    'value' => 'guest',
                ],
            ],
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $translator->translate('Save'),
                ],
            ],
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
