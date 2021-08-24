<?php

namespace Company\Form;

use Laminas\Filter\{
    StringTrim,
    StripTags,
};
use Laminas\Form\Element\{
    Submit,
    Text,
};
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\StringLength;

class JobLabel extends Form implements InputFilterProviderInterface
{
    public function __construct()
    {
        // we want to ignore the name passed
        parent::__construct();
        $this->setAttribute('method', 'post');

        $this->add(
            [
                'name' => 'name',
                'type' => Text::class,
            ]
        );

        $this->add(
            [
                'name' => 'nameEn',
                'type' => Text::class,
            ]
        );

        $this->add(
            [
                'name' => 'abbreviation',
                'type' => Text::class,
            ]
        );

        $this->add(
            [
                'name' => 'abbreviationEn',
                'type' => Text::class,
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
