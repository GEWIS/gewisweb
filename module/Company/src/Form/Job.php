<?php

declare(strict_types=1);

namespace Company\Form;

use Application\Form\Localisable as LocalisableForm;
use Company\Mapper\Job as JobMapper;
use Company\Model\JobCategory as JobCategoryModel;
use Company\Model\JobLabel as JobLabelModel;
use Laminas\Filter\StringTrim;
use Laminas\Filter\StripTags;
use Laminas\Filter\ToNull;
use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\Email;
use Laminas\Form\Element\File;
use Laminas\Form\Element\MultiCheckbox;
use Laminas\Form\Element\Select;
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
use Override;

use function intval;

class Job extends LocalisableForm implements InputFilterProviderInterface
{
    private string $companySlug;

    private ?string $currentSlug = null;

    /**
     * @param JobCategoryModel[] $categories
     * @param JobLabelModel[]    $labels
     */
    public function __construct(
        Translator $translator,
        private readonly JobMapper $mapper,
        array $categories,
        array $labels,
    ) {
        parent::__construct($translator);

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
                'name' => 'slugName',
                'type' => Text::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Slug'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'category',
                'type' => Select::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Category'),
                    'empty_option' => $this->getTranslator()->translate('Select a job category'),
                    'value_options' => $categoryOptions,
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
                'attributes' => [
                    'value' => '1',
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
                'name' => 'name',
                'type' => Text::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Name'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'nameEn',
                'type' => Text::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Name'),
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
                'name' => 'location',
                'type' => Text::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Location'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'locationEn',
                'type' => Text::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Location'),
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
                'name' => 'attachment',
                'type' => File::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Attachment'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'attachmentEn',
                'type' => File::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Attachment'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'labels',
                'type' => MultiCheckbox::class,
                'options' => [
                    'label' => $this->getTranslator()->translate('Labels'),
                    'value_options' => $labelOptions,
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

    public function setCompanySlug(string $companySlug): void
    {
        $this->companySlug = $companySlug;
    }

    public function setCurrentSlug(string $currentSlug): void
    {
        $this->currentSlug = $currentSlug;
    }

    #[Override]
    public function getInputFilterSpecification(): array
    {
        $filter = parent::getInputFilterSpecification();

        $filter += [
            'slugName' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Callback::class,
                        'options' => [
                            'callback' => $this->isSlugUnique(...),
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
            ],
            'category' => [
                'required' => true,
                'filters' => [
                    [
                        'name' => ToNull::class,
                    ],
                ],
            ],
            'published' => [
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
            'labels' => [
                'required' => false,
            ],
        ];

        return $filter;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function createLocalisedInputFilterSpecification(string $suffix = ''): array
    {
        return [
            'name' . $suffix => [
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
            'website' . $suffix => [
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
            'location' . $suffix => [
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
            'attachment' . $suffix => [
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
                    ],
                ],
            ],
        ];
    }

    /**
     * Checks if a given `slug` is unique. (Callback for validation).
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function isSlugUnique(
        string $value,
        array $context,
    ): bool {
        $category = $context['category'];

        // Don't validate if the job category is empty. Note that this is an empty string, null only exists after
        // validation of the form.
        if ('' === $category) {
            return false;
        }

        if ($this->currentSlug === $value) {
            return true;
        }

        return $this->mapper->isSlugNameUnique($this->companySlug, $value, intval($category));
    }
}
