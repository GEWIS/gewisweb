<?php

namespace Company\Form;

use Company\Mapper\Company as CompanyMapper;
use Laminas\Filter\{
    StringTrim,
    StripTags,
    ToNull,
};
use Laminas\Form\Element\{
    Checkbox,
    Email,
    File,
    Submit,
    Text,
    Textarea,
};
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
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

class Company extends Form implements InputFilterProviderInterface
{
    /**
     * @var CompanyMapper
     */
    private CompanyMapper $mapper;

    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * @var string|null $currentSlug
     */
    private ?string $currentSlug = null;

    public function __construct(CompanyMapper $mapper, Translator $translator)
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
                'name' => 'slugName',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Slug'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'logo',
                'type' => File::class,
                'options' => [
                    'label' => $this->translator->translate('Logo'),
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
                'name' => 'contactName',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Name'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'contactAddress',
                'type' => Textarea::class,
                'options' => [
                    'label' => $this->translator->translate('Address'),
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
                'name' => 'slogan',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Slogan'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'sloganEn',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Slogan'),
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
        $filter = [
            'name' => [
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
            'slugName' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Callback::class,
                        'options' => [
                            'callback' => [$this, 'isSlugNameUnique'],
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
            'logo' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => Extension::class,
                        'options' => [
                            'extension' => [
                                'png',
                                'jpg',
                                'jpeg',
                                'gif',
                                'bmp',
                            ],
                        ],
                    ],
                    [
                        'name' => MimeType::class,
                        'options' => [
                            'image/png',
                            'image/jpeg',
                            'image/gif',
                            'image/bmp',
                        ],
                    ],
                ],
                'filters' => [
                    [
                        'name' => ToNull::class,
                    ]
                ],
            ],
            'hidden' => [
                'required' => true,
            ],
            'contactName' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'encoding' => 'UTF-8',
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
            'contactAddress' => [
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
    protected function inputFilterGeneric(string $languageSuffix = ''): array
    {
        return [
            'slogan' . $languageSuffix => [
                'required' => false,
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
            'website' . $languageSuffix => [
                'required' => true,
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
        ];
    }

    /**
     * @param string|null $slugName
     */
    public function setCurrentSlug(?string $slugName): void
    {
        $this->currentSlug = $slugName;
    }

    /**
     * Determine if the slug is unique.
     *
     * @param string $slugName
     *
     * @return bool
     */
    public function isSlugNameUnique(string $slugName): bool
    {
        if ($this->currentSlug === $slugName) {
            return true;
        }

        return null === $this->mapper->findCompanyBySlugName($slugName);
    }
}
