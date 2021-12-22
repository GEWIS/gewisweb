<?php

namespace Frontpage\Form;

use Laminas\Form\Element\{
    Checkbox,
    Submit,
    Text,
    Textarea,
};
use Laminas\Form\Form;
use Laminas\Mvc\I18n\Translator;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\StringLength;

class NewsItem extends Form implements InputFilterProviderInterface
{
    /**
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
    {
        parent::__construct();

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
                'name' => 'pinned',
                'type' => Checkbox::class,
                'options' => [
                    'checked_value' => '1',
                    'unchecked_value' => '0',
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
