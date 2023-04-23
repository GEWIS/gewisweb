<?php

declare(strict_types=1);

namespace Frontpage\Form;

use Frontpage\Form\PollOption as PollOptionFieldset;
use Laminas\Form\Element\{
    Collection,
    Submit,
    Text,
};
use Laminas\Filter\StringTrim;
use Laminas\Form\Form;
use Laminas\Mvc\I18n\Translator;
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
                'type' => Text::class,
                'options' => [
                    'label' => $translator->translate('Dutch question'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'englishQuestion',
                'type' => Text::class,
                'options' => [
                    'label' => $translator->translate('English question'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'options',
                'type' => Collection::class,
                'options' => [
                    'count' => 2,
                    'should_create_template' => true,
                    'allow_add' => true,
                    'target_element' => [
                        'type' => PollOptionFieldset::class,
                    ],
                ],
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
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
    public function getInputFilterSpecification(): array
    {
        return [
            'dutchQuestion' => [
                'required' => true,
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                ],
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
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                ],
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
