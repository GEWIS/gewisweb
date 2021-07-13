<?php

namespace Frontpage\Form;

use Laminas\Form\Form;
use Laminas\I18n\Translator\TranslatorInterface as Translator;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\StringLength;

class Poll extends Form implements InputFilterProviderInterface
{
    public function __construct(Translator $translator)
    {
        parent::__construct();

        $this->add(
            [
            'name' => 'dutchQuestion',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Dutch question'),
            ],
            ]
        );

        $this->add(
            [
            'name' => 'englishQuestion',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('English question'),
            ],
            ]
        );

        $this->add(
            [
            'name' => 'options',
            'type' => 'Laminas\Form\Element\Collection',
            'options' => [
                'count' => 2,
                'should_create_template' => true,
                'allow_add' => true,
                'target_element' => [
                    'type' => 'Frontpage\Form\PollOption',
                ],
            ],
            ]
        );

        $this->add(
            [
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => $translator->translate('Submit'),
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
            'dutchQuestion' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 5,
                            'max' => 128,
                        ],
                    ],
                ],
            ],
            'englishQuestion' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 5,
                            'max' => 128,
                        ],
                    ],
                ],
            ],
        ];
    }
}
