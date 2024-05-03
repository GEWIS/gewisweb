<?php

declare(strict_types=1);

namespace Photo\Form;

use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\DateTimeLocal;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;

class Album extends Form implements InputFilterProviderInterface
{
    public function __construct(private Translator $translator)
    {
        parent::__construct();

        $this->add(
            [
                'name' => 'name',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Album title'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'startDateTime',
                'type' => DateTimeLocal::class,
                'options' => [
                    'label' => $this->translator->translate('Start date'),
                    'format' => 'Y-m-d\TH:i',
                ],
            ],
        );

        $this->add(
            [
                'name' => 'endDateTime',
                'type' => DateTimeLocal::class,
                'options' => [
                    'label' => $this->translator->translate('End date'),
                    'format' => 'Y-m-d\TH:i',
                ],
            ],
        );

        $this->add(
            [
                'name' => 'published',
                'type' => Checkbox::class,
                'options' => [
                    'label' => $this->translator->translate('Published'),
                    'checked_value' => '1',
                    'unchecked_value' => '0',
                ],
                'attributes' => [
                    'value' => '0',
                ],
            ],
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'options' => [
                    'label' => $this->translator->translate('Save'),
                ],
            ],
        );
    }

    public function getInputFilterSpecification(): array
    {
        return [
            'name' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => NotEmpty::class,
                    ],
                    [
                        'name' => Regex::class,
                        'options' => [
                            'pattern' => '/^[\p{L}\p{N}\-\.\:\@\^\&\*\?\!\(\)\'\"\s]+$/u',
                            'messages' => [
                                Regex::NOT_MATCH => $this->translator->translate(
                                    'Album name can only contain letters, numbers, -, ., :, @, ^, &, *, ?, !, (, ), \', ", and spaces',
                                ),
                            ],
                        ],
                    ],
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 3,
                            'max' => 75,
                        ],
                    ],
                ],
            ],
            'startDateTime' => [
                'required' => false,
            ],
            'endDateTime' => [
                'required' => false,
            ],
            'published' => [
                'required' => true,
            ],
        ];
    }
}
