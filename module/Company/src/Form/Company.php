<?php

namespace Company\Form;

use Application\Form\Localisable as LocalisableForm;
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

class Company extends LocalisableForm implements InputFilterProviderInterface
{
    /**
     * @var string|null $currentSlug
     */
    private ?string $currentSlug = null;

    public function __construct(
        private readonly CompanyMapper $mapper,
        Translator $translator,
    ) {
        // we want to ignore the name passed
        parent::__construct($translator);

        $this->setAttribute('method', 'post');

        $this->add(
            [
                'name' => 'name',
                'type' => Text::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Name'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'slugName',
                'type' => Text::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Slug'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'logo',
                'type' => File::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Logo'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'published',
                'type' => Checkbox::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Published'),
                    'checked_value' => '1',
                    'unchecked_value' => '0',
                ],
            ]
        );

        $this->add(
            [
                'name' => 'representativeName',
                'type' => Text::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Name'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'representativeEmail',
                'type' => Email::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('E-mail Address'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'contactName',
                'type' => Text::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Name'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'contactAddress',
                'type' => Textarea::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Address'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'contactEmail',
                'type' => Email::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('E-mail Address'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'contactPhone',
                'type' => Text::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Phone Number'),
                ],
            ]
        );

        // All language attributes.
        $this->add(
            [
                'name' => 'slogan',
                'type' => Text::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Slogan'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'sloganEn',
                'type' => Text::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Slogan'),
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
                    'label' => $this->getTranslator()->translate('Website'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'websiteEn',
                'type' => Text::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Website'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'description',
                'type' => Textarea::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Description'),
                ],
            ]
        );

        $this->add(
            [
                'name' => 'descriptionEn',
                'type' => Textarea::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Description'),
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
        $filter = parent::getInputFilterSpecification();

        $filter += [
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
                                Callback::INVALID_VALUE => $this->getTranslator()->translate('This slug is already taken'),
                            ],
                        ],
                    ],
                    [
                        'name' => Regex::class,
                        'options' => [
                            'pattern' => '/^[0-9a-zA-Z_\-\.]+$/',
                            'messages' => [
                                Regex::ERROROUS => $this->getTranslator()->translate('This slug contains invalid characters'),
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
                    ],
                ],
            ],
            'published' => [
                'required' => true,
            ],
            'representativeName' => [
                'required' => true,
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
            'representativeEmail' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => EmailAddress::class,
                        'options' => [
                            'messages' => [
                                'emailAddressInvalidFormat' => $this->getTranslator()->translate(
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
                ],
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
                                'emailAddressInvalidFormat' => $this->getTranslator()->translate(
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

        return $filter;
    }

    /**
     * @inheritDoc
     */
    protected function createLocalisedInputFilterSpecification(string $suffix = ''): array
    {
        return [
            'slogan' . $suffix => [
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
            'website' . $suffix => [
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
            'description' . $suffix => [
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
