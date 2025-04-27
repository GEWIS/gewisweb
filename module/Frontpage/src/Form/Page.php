<?php

declare(strict_types=1);

namespace Frontpage\Form;

use Application\Model\Enums\Languages;
use Frontpage\Mapper\Page as PageMapper;
use Frontpage\Model\FrontpageLocalisedText;
use Laminas\Filter\StringToLower;
use Laminas\Filter\ToNull;
use Laminas\Form\Element\Select;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\Form\Element\Textarea;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;
use User\Model\Enums\UserRoles;

use function mb_strtolower;

/**
 * @psalm-suppress MissingTemplateParam
 */
class Page extends Form implements InputFilterProviderInterface
{
    private ?FrontpageLocalisedText $currentCategory = null;
    private ?FrontpageLocalisedText $currentSubCategory = null;
    private ?FrontpageLocalisedText $currentName = null;

    public function __construct(
        private readonly Translator $translator,
        private readonly PageMapper $pageMapper,
    ) {
        parent::__construct();

        $this->add(
            [
                'name' => 'category',
                'type' => Text::class,
            ],
        );

        $this->add(
            [
                'name' => 'categoryEn',
                'type' => Text::class,
            ],
        );

        $this->add(
            [
                'name' => 'subCategory',
                'type' => Text::class,
            ],
        );

        $this->add(
            [
                'name' => 'subCategoryEn',
                'type' => Text::class,
            ],
        );

        $this->add(
            [
                'name' => 'name',
                'type' => Text::class,
            ],
        );

        $this->add(
            [
                'name' => 'nameEn',
                'type' => Text::class,
            ],
        );

        $this->add(
            [
                'name' => 'titleEn',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('English title'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'title',
                'type' => Text::class,
                'options' => [
                    'label' => $this->translator->translate('Dutch title'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'contentEn',
                'type' => Textarea::class,
                'options' => [
                    'label' => $this->translator->translate('English content'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'content',
                'type' => Textarea::class,
                'options' => [
                    'label' => $this->translator->translate('Dutch content'),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'requiredRole',
                'type' => Select::class,
                'options' => [
                    'label' => $this->translator->translate('Required role'),
                    'value_options' => UserRoles::getFormValueOptions($translator),
                ],
            ],
        );

        $this->add(
            [
                'name' => 'submit',
                'type' => Submit::class,
                'attributes' => [
                    'value' => $this->translator->translate('Save'),
                ],
            ],
        );
    }

    public function setCurrentValues(
        FrontpageLocalisedText $currentCategory,
        FrontpageLocalisedText $currentSubCategory,
        FrontpageLocalisedText $currentName,
    ): void {
        $this->currentCategory = $currentCategory;
        $this->currentSubCategory = $currentSubCategory;
        $this->currentName = $currentName;
    }

    public function isValid(): bool
    {
        $valid = parent::isValid();

        // Convert to something that is consistent.
        $categoryEn = mb_strtolower($this->data['categoryEn'] ?? '');
        $category = mb_strtolower($this->data['category'] ?? '');
        $subCategoryEn = mb_strtolower($this->data['subCategoryEn'] ?? '');
        $subCategory = mb_strtolower($this->data['subCategory'] ?? '');
        $nameEn = mb_strtolower($this->data['nameEn'] ?? '');
        $name = mb_strtolower($this->data['name'] ?? '');

        // If we are editing a page, we may have to only change one route not both.
        $dutchRouteEqual = false;
        $englishRouteEqual = false;

        if (
            null !== $this->currentCategory
            && null !== $this->currentSubCategory
            && null !== $this->currentName
        ) {
            // This is not a new page, otherwise these values would have been `null`. Edits of pages are guaranteed to
            // always have all three values populated (otherwise Doctrine will get angry).
            $dutchRouteEqual = mb_strtolower($this->currentCategory->getValueNL() ?? '') === $category
                && mb_strtolower($this->currentSubCategory->getValueNL() ?? '') === $subCategory
                && mb_strtolower($this->currentName->getValueNL() ?? '') === $name;

            $englishRouteEqual = mb_strtolower($this->currentCategory->getValueEN() ?? '') === $categoryEn
                && mb_strtolower($this->currentSubCategory->getValueEN() ?? '') === $subCategoryEn
                && mb_strtolower($this->currentName->getValueEN() ?? '') === $nameEn;

            // If both routes are still the same, we do not have to look up anything from the database.
            if (
                $dutchRouteEqual
                && $englishRouteEqual
            ) {
                return $valid;
            }
        }

        if (!$dutchRouteEqual) {
            $potentialDutchPage = $this->pageMapper->findPage(
                Languages::Dutch,
                $category,
                '' === $subCategory ? null : $subCategory,
                '' === $name ? null : $name,
            );

            if (null !== $potentialDutchPage) {
                // Try to show the user which part of the route is already used.
                if ($potentialDutchPage->getCategory()->getValueNL() === $category) {
                    $this->get('category')->setMessages([
                        $this->translator->translate('This route already exists for another page!'),
                    ]);
                }

                if ($potentialDutchPage->getSubCategory()->getValueNL() === $subCategory) {
                    $this->get('subCategory')->setMessages([
                        $this->translator->translate('This route already exists for another page!'),
                    ]);
                }

                if ($potentialDutchPage->getName()->getValueNL() === $name) {
                    $this->get('name')->setMessages([
                        $this->translator->translate('This route already exists for another page!'),
                    ]);
                }

                $valid = false;
            }
        }

        if (!$englishRouteEqual) {
            $potentialEnglishPage = $this->pageMapper->findPage(
                Languages::English,
                $categoryEn,
                '' === $subCategoryEn ? null : $subCategoryEn,
                '' === $nameEn ? null : $nameEn,
            );

            if (null !== $potentialEnglishPage) {
                // Try to show the user which part of the route is already used.
                if ($potentialEnglishPage->getCategory()->getValueEN() === $categoryEn) {
                    $this->get('category')->setMessages([
                        $this->translator->translate('This route already exists for another page!'),
                    ]);
                }

                if ($potentialEnglishPage->getSubCategory()->getValueEN() === $subCategoryEn) {
                    $this->get('subCategoryEn')->setMessages([
                        $this->translator->translate('This route already exists for another page!'),
                    ]);
                }

                if ($potentialEnglishPage->getName()->getValueEN() === $nameEn) {
                    $this->get('nameEn')->setMessages([
                        $this->translator->translate('This route already exists for another page!'),
                    ]);
                }

                $valid = false;
            }
        }

        $this->isValid = $valid;

        return $valid;
    }

    /**
     * Should return an array specification compatible with
     * {@link \Laminas\InputFilter\Factory::createInputFilter()}.
     */
    public function getInputFilterSpecification(): array
    {
        $filter = [];

        foreach (['', 'En'] as $suffix) {
            $filter += [
                'category' . $suffix => [
                    'required' => true,
                    'validators' => [
                        [
                            'name' => StringLength::class,
                            'options' => [
                                'min' => 3,
                                'max' => 32,
                            ],
                        ],
                        [
                            'name' => Regex::class,
                            'options' => [
                                'pattern' => '/^[0-9a-zA-Z_\-]+$/',
                                'messages' => [
                                    Regex::NOT_MATCH => $this->translator->translate(
                                        'This route part contains invalid characters.',
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
                'subCategory' . $suffix => [
                    'required' => false,
                    'validators' => [
                        [
                            'name' => StringLength::class,
                            'options' => [
                                'min' => 2,
                                'max' => 32,
                            ],
                        ],
                        [
                            'name' => Regex::class,
                            'options' => [
                                'pattern' => '/^[0-9a-zA-Z_\-]+$/',
                                'messages' => [
                                    Regex::NOT_MATCH => $this->translator->translate(
                                        'This route part contains invalid characters.',
                                    ),
                                ],
                            ],
                        ],
                    ],
                    'filters' => [
                        [
                            'name' => StringToLower::class,
                        ],
                        [
                            'name' => ToNull::class,
                        ],
                    ],
                ],
                'name' . $suffix => [
                    'required' => false,
                    'validators' => [
                        [
                            'name' => StringLength::class,
                            'options' => [
                                'min' => 2,
                                'max' => 32,
                            ],
                        ],
                        [
                            'name' => Regex::class,
                            'options' => [
                                'pattern' => '/^[0-9a-zA-Z_\-]+$/',
                                'messages' => [
                                    Regex::NOT_MATCH => $this->translator->translate(
                                        'This route part contains invalid characters.',
                                    ),
                                ],
                            ],
                        ],
                    ],
                    'filters' => [
                        [
                            'name' => StringToLower::class,
                        ],
                        [
                            'name' => ToNull::class,
                        ],
                    ],
                ],
                'title' . $suffix => [
                    'required' => true,
                    'validators' => [
                        [
                            'name' => StringLength::class,
                            'options' => [
                                'min' => 3,
                                'max' => 64,
                            ],
                        ],
                    ],
                ],
            ];
        }

        foreach (['subCategory', 'name'] as $field) {
            if (
                (
                    !isset($this->data[$field . 'En'])
                    || '' === $this->data[$field . 'En']
                )
                && (
                    !isset($this->data[$field])
                    || '' === $this->data[$field]
                )
            ) {
                continue;
            }

            $filter[$field . 'En']['required'] = true;
            $filter[$field]['required'] = true;
        }

        return $filter + [
            'contentEn' => [
                'required' => true,
            ],
            'content' => [
                'required' => true,
            ],
            'requiredRole' => [
                'required' => true,
            ],
        ];
    }
}
