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
                    'label' => $translator->translate('Question'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'englishQuestion',
                'type' => Text::class,
                'options' => [
                    'label' => $translator->translate('Question'),
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
                    'target_element' => new PollOptionFieldset($translator),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $translator->translate('Submit'),
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
