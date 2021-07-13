<?php

namespace Frontpage\Form;

use Laminas\Form\Form;
use Laminas\I18n\Translator\TranslatorInterface as Translator;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\StringLength;

class NewsItem extends Form implements InputFilterProviderInterface
{
    public function __construct(Translator $translator)
    {
        parent::__construct();

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
            'name' => 'pinned',
            'type' => 'Laminas\Form\Element\Checkbox',
            'options' => [
                'checked_value' => 1,
                'unchecked_value' => 0,
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
            'dutchTitle' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 3,
                            'max' => 75,
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
                            'max' => 75,
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
        ];
    }
}
