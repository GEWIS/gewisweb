<?php

declare(strict_types=1);

namespace Company\Form;

use Application\Form\Localisable as LocalisableForm;
use Company\Mapper\Company as CompanyMapper;
use Laminas\Filter\StringToLower;
use Laminas\Filter\StringTrim;
use Laminas\Filter\StripTags;
use Laminas\Filter\ToNull;
use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\Email;
use Laminas\Form\Element\File;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\Form\Element\Textarea;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\Callback;
use Laminas\Validator\EmailAddress;
use Laminas\Validator\File\Extension;
use Laminas\Validator\File\MimeType;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;
use Laminas\Validator\Uri;

use function mb_strtolower;

class Company extends LocalisableForm implements InputFilterProviderInterface
{
    private ?string $currentSlug = null;

    private ?string $currentRepresentativeEmail = null;

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
            ],
        );

        $this->add(
            [
                'name' => 'slugName',
                'type' => Text::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Slug'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'logo',
                'type' => File::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Logo'),
                ],
            ],
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
            ],
        );

        $this->add(
            [
                'name' => 'representativeName',
                'type' => Text::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Name'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'representativeEmail',
                'type' => Email::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('E-mail Address'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'contactName',
                'type' => Text::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Name'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'contactAddress',
                'type' => Textarea::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Address'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'contactEmail',
                'type' => Email::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('E-mail Address'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'contactPhone',
                'type' => Text::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Phone Number'),
                ],
            ],
        );

        // All language attributes.
        $this->add(
            [
                'name' => 'slogan',
                'type' => Text::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Slogan'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'sloganEn',
                'type' => Text::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Slogan'),
                ],
            ],
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
            ],
        );

        $this->add(
            [
                'name' => 'websiteEn',
                'type' => Text::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Website'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'description',
                'type' => Textarea::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Description'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'descriptionEn',
                'type' => Textarea::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Description'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
            ],
        );
    }

    /**
     * @return array
     *
     * @inheritDoc
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
                                Callback::INVALID_VALUE => $this->getTranslator()->translate(
                                    'This slug is already taken',
                                ),
                            ],
                        ],
                    ],
                    [
                        'name' => Regex::class,
                        'options' => [
                            'pattern' => '/^[0-9a-zA-Z_\-\.]+$/',
                            'messages' => [
                                Regex::NOT_MATCH => $this->getTranslator()->translate(
                                    'This slug contains invalid characters',
                                ),
                            ],
                        ],
                    ],
                ],
                'filters' => [
                    [
                        'name' => StringToLower::class,
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
                                    'E-mail address format is not valid.',
                                ),
                            ],
                        ],
                    ],
                    [
                        'name' => Callback::class,
                        'options' => [
                            'messages' => [
                                Callback::INVALID_VALUE => $this->getTranslator()->translate(
                                    'The e-mail address must be unique across companies.',
                                ),
                            ],
                            'callback' => [$this, 'isRepresentativeEmailUnique'],
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
                                'emailAddressInvalidFormat' => $this->getTranslator()->translate(
                                    'E-mail address format is not valid',
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

    public function setCurrentSlug(?string $slugName): void
    {
        $this->currentSlug = $slugName;
    }

    public function setCurrentRepresentativeEmail(?string $email): void
    {
        $this->currentRepresentativeEmail = $email;
    }

    /**
     * Determine if the slug is unique.
     */
    public function isSlugNameUnique(string $slugName): bool
    {
        if (
            null !== $this->currentSlug
            && mb_strtolower($this->currentSlug) === mb_strtolower($slugName)
        ) {
            return true;
        }

        return null === $this->mapper->findCompanyBySlugName($slugName);
    }

    public function isRepresentativeEmailUnique(string $email): bool
    {
        if (
            null !== $this->currentRepresentativeEmail
            && mb_strtolower($this->currentRepresentativeEmail) === mb_strtolower($email)
        ) {
            return true;
        }

        return null === $this->mapper->findCompanyByRepresentativeEmail($email);
    }
}
