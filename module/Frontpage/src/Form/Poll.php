<?php

declare(strict_types=1);

namespace Frontpage\Form;

use Frontpage\Form\PollOption as PollOptionFieldset;
use Laminas\Filter\StringTrim;
use Laminas\Form\Element\Collection;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\Callback;
use Laminas\Validator\StringLength;

use function str_ends_with;

class Poll extends Form implements InputFilterProviderInterface
{
    public function __construct(private readonly Translator $translator)
    {
        parent::__construct();

        $this->add(
            [
                'name' => 'dutchQuestion',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Question'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'englishQuestion',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Question'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'options',
                'type' => Collection::class,
                'options' => [
                    'count' => 2,
                    'should_create_template' => true,
                    'template_placeholder' => '__option__',
                    'allow_add' => true,
                    'target_element' => new PollOptionFieldset($this->translator),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $this->translator->translate('Submit'),
                ],
            ],
        );
    }

    /**
     * Should return an array specification compatible with
     * {@link \Laminas\InputFilter\Factory::createInputFilter()}.
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
                    [
                        'name' => Callback::class,
                        'options' => [
                            'messages' => [
                                Callback::INVALID_VALUE => $this->translator->translate(
                                    'The question must end with a question mark',
                                ),
                            ],
                            'callback' => static function ($value) {
                                return str_ends_with($value, '?');
                            },
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
                    [
                        'name' => Callback::class,
                        'options' => [
                            'messages' => [
                                Callback::INVALID_VALUE => $this->translator->translate(
                                    'The question must end with a question mark',
                                ),
                            ],
                            'callback' => static function ($value) {
                                return str_ends_with($value, '?');
                            },
                        ],
                    ],
                ],
            ],
        ];
    }
}
