<?php

namespace Company\Form;

use Laminas\Mvc\I18n\Translator;
use Laminas\Filter\{
    StringTrim,
    StripTags,
};
use Laminas\Form\Element\{
    Checkbox,
    Submit,
    Text,
};
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\StringLength;

class JobLabel extends Form implements InputFilterProviderInterface
{
    /**
     * @var Translator
     */
    private Translator $translator;

    public function __construct(Translator $translator)
    {
        // we want to ignore the name passed
        parent::__construct();
        $this->translator = $translator;
        $this->setAttribute('method', 'post');

        // All language attributes.
        $this->add(
            [
                'name' => 'language_dutch',
                'type' => Checkbox::class,
                'options' => [
                    'label' => $this->translator->translate('Enable Dutch Translations'),
                    'checked_value' => 1,
                    'unchecked_value' => 0,
                ],
            ]
        );

        $this->add(
            [
                'name' => 'language_english',
                'type' => Checkbox::class,
                'options' => [
                    'label' => $this->translator->translate('Enable English Translations'),
                    'checked_value' => 1,
                    'unchecked_value' => 0,
                ],
            ]
        );

        $this->add(
            [
                'name' => 'name',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Name'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'nameEn',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Name'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'abbreviation',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Abbreviation'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'abbreviationEn',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Abbreviation'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
            ]
        );
    }

    /**
     * @inheritDoc
     *
     * @return array
     */
    public function getInputFilterSpecification(): array
    {
        $filter = [];

        foreach (['', 'En'] as $languageSuffix) {
            $filter['name' . $languageSuffix] = [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 2,
                            'max' => 127,
                        ],
                    ],
                ],
                'filters' => [
                    [
                        'name' => StripTags::class,
                    ],
                    [
                        'name' => StringTrim::class,
                    ],
                ],
            ];
            $filter['abbreviation' . $languageSuffix] = [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 2,
                            'max' => 127,
                        ],
                    ],
                ],
                'filters' => [
                    [
                        'name' => StripTags::class,
                    ],
                    [
                        'name' => StringTrim::class,
                    ],
                ],
            ];
        }

        return $filter;
    }
}
