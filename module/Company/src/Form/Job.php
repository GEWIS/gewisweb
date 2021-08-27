<?php

namespace Company\Form;

use Company\Mapper\Job as JobMapper;
use Laminas\Filter\{
    StringTrim,
    StripTags,
    ToNull,
};
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Form\Element\{
    Checkbox,
    Email,
    File,
    MultiCheckbox,
    Select,
    Submit,
    Text,
    Textarea,
};
use Laminas\Form\Form;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\{
    Callback,
    EmailAddress,
    File\Extension,
    File\MimeType,
    Regex,
    StringLength,
    Uri,
};

class Job extends Form implements InputFilterProviderInterface
{
    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * @var JobMapper
     */
    private JobMapper $mapper;

    /**
     * @var string
     */
    private string $companySlug;

    /**
     * @var string|null
     */
    private ?string $currentSlug = null;

    public function __construct(JobMapper $mapper, Translator $translator, array $categories, array $labels)
    {
        // we want to ignore the name passed
        parent::__construct();
        $this->translator = $translator;
        $this->mapper = $mapper;

        $this->setAttribute('method', 'post');

        $categoryOptions = [];
        foreach ($categories as $category) {
            $categoryOptions[$category->getId()] = $category->getName();
        }

        $labelOptions = [];
        foreach ($labels as $label) {
            $labelOptions[$label->getId()] = $label->getName();
        }

        $this->add(
            [
                'name' => 'slug',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Slug'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'category',
                'type' => Select::class,
                'options' => [
                    'label' => $this->translator->translate('Category'),
                    'empty_option' => $this->translator->translate('Select a job category'),
                    'value_options' => $categoryOptions,
                ],
            ]
        );

        $this->add(
            [
                'name' => 'active',
                'type' => Checkbox::class,
                'options' => [
                    'label' => $this->translator->translate('Active'),
                    'checked_value' => 1,
                    'unchecked_value' => 0,
                ],
            ]
        );

        $this->add(
            [
                'name' => 'contactName',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Name'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'contactEmail',
                'type' => Email::class,
                'options' => [
                    'label' => $this->translator->translate('E-mail Address'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'contactPhone',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Phone Number'),
                ],
            ]
        );

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

        /**
         * {@link \Laminas\Form\Element\Url} defaults to '`required` => true', which breaks our custom language
         * validation. Hence, we use {@link \Laminas\Form\Element\Text} with the proper validator.
         */
        $this->add(
            [
                'name' => 'website',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Website'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'websiteEn',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Website'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'location',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Location'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'locationEn',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Location'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'description',
                'type' => Textarea::class,
                'options' => [
                    'label' => $this->translator->translate('Description'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'descriptionEn',
                'type' => Textarea::class,
                'options' => [
                    'label' => $this->translator->translate('Description'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'attachment',
                'type' => File::class,
                'options' => [
                    'label' => $this->translator->translate('Attachment'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'attachmentEn',
                'type' => File::class,
                'options' => [
                    'label' => $this->translator->translate('Attachment'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'labels',
                'type' => MultiCheckbox::class,
                'options' => [
                    'label' => $this->translator->translate('Labels'),
                    'value_options' => $labelOptions,
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
     * @param string $companySlug
     */
    public function setCompanySlug(string $companySlug): void
    {
        $this->companySlug = $companySlug;
    }

    /**
     * @param string $currentSlug
     */
    public function setCurrentSlug(string $currentSlug): void
    {
        $this->currentSlug = $currentSlug;
    }

    /**
     * @return array
     */
    public function getInputFilterSpecification(): array
    {
        $filter = [
            'slug' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Callback::class,
                        'options' => [
                            'callback' => [$this, 'isSlugUnique'],
                            'messages' => [
                                Callback::INVALID_VALUE => $this->translator->translate('This slug is already taken'),
                            ],
                        ],
                    ],
                    [
                        'name' => Regex::class,
                        'options' => [
                            'pattern' => '/^[0-9a-zA-Z_\-\.]*$/',
                            'messages' => [
                                Regex::ERROROUS => $this->translator->translate('This slug contains invalid characters'),
                            ],
                        ],
                    ],
                ],
            ],
            'category' => [
                'required' => true,
            ],
            'active' => [
                'required' => true,
            ],
            'contactName' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'max' => 200,
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
                    [
                        'name' => ToNull::class,
                    ],
                ],
            ],
            'contactEmail' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => EmailAddress::class,
                        'options' => [
                            'messages' => [
                                'emailAddressInvalidFormat' => $this->translator->translate(
                                    'E-mail address format is not valid'
                                ),
                            ],
                        ],
                    ],
                ],
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                    [
                        'name' => ToNull::class,
                    ],
                ],
            ],
            'contactPhone' => [
                'required' => false,
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                    [
                        'name' => ToNull::class,
                    ],
                ],
            ],
        ];

        if (
            isset($this->data['language_english'])
            && $this->data['language_english']
        ) {
            $filter += $this->inputFilterGeneric('En');
        }

        if (
            isset($this->data['language_dutch'])
            && $this->data['language_dutch']
        ) {
            $filter += $this->inputFilterGeneric();
        }

        // One of the language_dutch or language_english needs to set. If not, display a message at both, indicating
        // that they need to be set.
        if (
            (isset($this->data['language_dutch']) && !$this->data['language_dutch'])
            && (isset($this->data['language_english']) && !$this->data['language_english'])
        ) {
            unset($this->data['language_dutch'], $this->data['language_english']);

            $filter += [
                'language_dutch' => [
                    'required' => true,
                ],
                'language_english' => [
                    'required' => true,
                ],
            ];
        }

        return $filter;
    }

    /**
     * Build a generic input filter.
     *
     * @param string $languageSuffix Suffix that is used for language fields to indicate that a field belongs to that
     * language
     *
     * @return array
     */
    public function inputFilterGeneric(string $languageSuffix = ''): array
    {
        return [
            'name' . $languageSuffix => [
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
            ],
            'website' . $languageSuffix => [
                'required' => false,
                'validators' => [
                    [
                        'name' => Uri::class,
                        'options' => [
                            'allowRelative' => false,
                        ],
                    ],
                ],
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                    [
                        'name' => ToNull::class,
                    ],
                ],
            ],
            'location' . $languageSuffix => [
                'required' => false,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'encoding' => 'UTF-8',
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
                    [
                        'name' => ToNull::class,
                    ],
                ],
            ],
            'description' . $languageSuffix => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 2,
                            'max' => 10000,
                        ],
                    ],
                ],
            ],
            'attachment' . $languageSuffix => [
                'required' => false,
                'validators' => [
                    [
                        'name' => Extension::class,
                        'options' => [
                            'pdf',
                        ],
                    ],
                    [
                        'name' => MimeType::class,
                        'options' => [
                            'application/pdf',
                        ],
                    ],
                ],
                'filters' => [
                    [
                        'name' => ToNull::class,
                    ]
                ],
            ],
        ];
    }

    /**
     * Checks if a given `slug` is unique. (Callback for validation).
     *
     * @param string $value
     * @param array $context
     *
     * @return bool
     */
    public function isSlugUnique(string $value, array $context): bool
    {
        $category = $context['category'];

        if ($this->currentSlug === $value) {
            return true;
        }

        return $this->mapper->isSlugNameUnique($this->companySlug, $value, $category);
    }
}
