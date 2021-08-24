<?php

namespace Company\Form;

use Company\Mapper\Category as CategoryMapper;
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
use Laminas\Validator\{
    Callback,
    StringLength,
};

class JobCategory extends Form implements InputFilterProviderInterface
{
    /**
     * @var CategoryMapper
     */
    private CategoryMapper $mapper;

    /**
     * @var string|null
     */
    private ?string $currentEnglishPluralName = null;

    public function __construct(CategoryMapper $mapper)
    {
        // we want to ignore the name passed
        parent::__construct();
        $this->mapper = $mapper;
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
                'name' => 'namePlural',
                'type' => Text::class,
            ]
        );

        $this->add(
            [
                'name' => 'namePluralEn',
                'type' => Text::class,
            ]
        );

        $this->add(
            [
                'name' => 'hidden',
                'type' => Checkbox::class,
                'options' => [
                    'checked_value' => 1,
                    'unchecked_value' => 0,
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
     * @return array
     */
    public function getInputFilterSpecification(): array
    {
        $filter = [
            'hidden' => [
                'required' => true,
            ],
        ];

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
            $filter['namePlural' . $languageSuffix] = [
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

        $filter['namePluralEn']['validators'][] = [
            'name' => Callback::class,
            ''
        ];

        return $filter;
    }

    /**
     * Determine if the plural of the name in English is unique.
     *
     * @param string $value
     *
     * @return bool
     */
    public function isEnglishPluralUnique(string $value): bool
    {
        if ($this->currentEnglishPluralName === $value) {
            return true;
        }

        return null === $this->mapper->findCategoryBySlug($value);
    }
}
