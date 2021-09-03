<?php

namespace Company\Form;

use Company\Mapper\Category as CategoryMapper;
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
     * @var Translator
     */
    private Translator $translator;

    /**
     * @var string|null
     */
    private ?string $currentEnglishPluralName = null;

    public function __construct(CategoryMapper $mapper, Translator $translator)
    {
        // we want to ignore the name passed
        parent::__construct();
        $this->mapper = $mapper;
        $this->translator = $translator;
        $this->setAttribute('method', 'post');

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
                'name' => 'pluralName',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Name (Plural)'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'pluralNameEn',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Name (Plural)'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'hidden',
                'type' => Checkbox::class,
                'options' => [
                    'label' => $this->translator->translate('Hidden'),
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
            $filter['pluralName' . $languageSuffix] = [
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

        $filter['pluralNameEn']['validators'][] = [
            'name' => Callback::class,
            'options' => [
                'callback' => [$this, 'isEnglishPluralUnique'],
                Callback::INVALID_VALUE => $this->translator->translate(
                    'This plural of the English name is already taken'
                ),
            ],
        ];

        return $filter;
    }

    /**
     * @param string|null $currentEnglishPluralName
     */
    public function setCurrentEnglishPluralName(?string $currentEnglishPluralName): void
    {
        $this->currentEnglishPluralName = $currentEnglishPluralName;
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
