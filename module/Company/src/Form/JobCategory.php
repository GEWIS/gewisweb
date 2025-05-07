<?php

declare(strict_types=1);

namespace Company\Form;

use Company\Mapper\Category as CategoryMapper;
use Company\Model\CompanyLocalisedText as CompanyLocalisedTextModel;
use Doctrine\ORM\NonUniqueResultException;
use Laminas\Filter\StringToLower;
use Laminas\Filter\StringTrim;
use Laminas\Filter\StripTags;
use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\Callback;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;
use Override;

use function mb_strtolower;

/**
 * @psalm-suppress MissingTemplateParam
 */
class JobCategory extends Form implements InputFilterProviderInterface
{
    private ?string $currentSlug = null;

    private ?string $currentSlugEn = null;

    public function __construct(
        private readonly Translator $translator,
        private readonly CategoryMapper $mapper,
    ) {
        // we want to ignore the name passed
        parent::__construct();

        $this->setAttribute('method', 'post');

        $this->add(
            [
                'name' => 'name',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Name'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'nameEn',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Name'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'pluralName',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Name (Plural)'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'pluralNameEn',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Name (Plural)'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'slug',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Slug'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'slugEn',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Slug'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'hidden',
                'type' => Checkbox::class,
                'options' => [
                    'label' => $this->translator->translate('Hidden'),
                    'checked_value' => '1',
                    'unchecked_value' => '0',
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

    #[Override]
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
            $filter['slug' . $languageSuffix] = [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 2,
                            'max' => 63,
                        ],
                    ],
                    [
                        'name' => Callback::class,
                        'options' => [
                            'callback' => $this->isSlugUnique(...),
                            'callbackOptions' => [
                                'languageSuffix' => $languageSuffix,
                            ],
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
                                Regex::NOT_MATCH => $this->translator->translate(
                                    'This slug contains invalid characters',
                                ),
                            ],
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
                        'name' => StringToLower::class,
                    ],
                ],
            ];
        }

        return $filter;
    }

    public function setCurrentSlug(CompanyLocalisedTextModel $currentSlug): void
    {
        $this->currentSlug = $currentSlug->getValueNL();
        $this->currentSlugEn = $currentSlug->getValueEN();
    }

    /**
     * Determine if the given slug is unique (in Dutch and English).
     *
     * @throws NonUniqueResultException
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function isSlugUnique(
        string $value,
        array $context,
        string $languageSuffix,
    ): bool {
        if (
            null !== $this->{'currentSlug' . $languageSuffix}
            && mb_strtolower((string) $this->{'currentSlug' . $languageSuffix}) === mb_strtolower($value)
        ) {
            return true;
        }

        return null === $this->mapper->findCategoryBySlug($value);
    }
}
