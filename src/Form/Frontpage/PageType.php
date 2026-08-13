<?php

declare(strict_types=1);

namespace App\Form\Frontpage;

use App\Entity\Application\Enums\Languages;
use App\Entity\Frontpage\FrontpageLocalisedText;
use App\Entity\Frontpage\Page;
use App\Entity\User\Enums\UserRoles;
use App\Form\Application\LocalisedTextType;
use App\Repository\Frontpage\PageRepository;
use App\Util\Application\SlugRule;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_keys;
use function array_map;
use function array_shift;
use function explode;
use function in_array;
use function str_contains;
use function Symfony\Component\Translation\t;
use function trim;

/**
 * A custom page: where it lives in the site, what it is called, who may read it and what it says.
 *
 * A page is addressed by its own words, which is what makes it a custom page rather than a row with an id in a URL.
 * Each part of that address is a segment of a public URL and is held to the shape of one; on top of that there are two
 * things to check that no single field can: no two pages may answer to the same address in the same language, and no
 * page may take an address the application already answers to itself.
 *
 * @extends AbstractType<Page>
 */
class PageType extends AbstractType
{
    private const string SLUG_MESSAGE = 'Use three to thirty-two lower-case letters, digits, '
        . 'underscores or hyphens, starting with a letter.';

    /** @var list<string>|null */
    private ?array $reservedCategories = null;

    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly RouterInterface $router,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $localised = [
            'data_class' => FrontpageLocalisedText::class,
        ];

        // Each part of the address is a segment of a public URL, so it is held to what a slug may look like. An empty
        // one is how a page higher up the tree says it has no sub-category or name, which is why the rule only
        // applies to what was actually written.
        $slug = $localised + [
            'value_constraints' => [
                new Regex(
                    pattern: SlugRule::BOUNDED_PATTERN,
                    message: self::SLUG_MESSAGE,
                ),
            ],
        ];

        $builder
            ->add(
                'category',
                LocalisedTextType::class,
                $slug + ['label' => t('Category')],
            )
            ->add(
                'subCategory',
                LocalisedTextType::class,
                $slug + ['label' => t('Sub-category')],
            )
            ->add(
                'name',
                LocalisedTextType::class,
                $slug + ['label' => t('Name')],
            )
            ->add(
                'title',
                LocalisedTextType::class,
                $localised + ['label' => t('Title')],
            )
            ->add(
                'requiredRole',
                EnumType::class,
                [
                    'label' => t('Who may read this page'),
                    'class' => UserRoles::class,
                    'choice_label' => static fn (UserRoles $role): string => $role->label()->getMessage(),
                ],
            )
            ->add(
                'content',
                LocalisedTextType::class,
                $localised + [
                    'label' => t('Content'),
                    'multiline' => true,
                ],
            );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            $this->validateAddress(...),
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Page::class]);
    }

    private function validateAddress(FormEvent $event): void
    {
        $page = $event->getData();
        if (!$page instanceof Page) {
            return;
        }

        $form = $event->getForm();

        foreach (
            [
                Languages::Dutch,
                Languages::English,
            ] as $language
        ) {
            $category = $this->slug(
                $page,
                $language,
                'category',
            );
            if (null === $category) {
                continue;
            }

            if (
                in_array(
                    $category,
                    $this->reservedCategories(),
                    true,
                )
            ) {
                $form->get('category')->addError(new FormError($this->translator->trans(
                    'The website already answers to this address, so a page cannot take it.',
                    [],
                    'validators',
                )));

                continue;
            }

            $existing = $this->pageRepository->findPage(
                $language,
                $category,
                $this->slug(
                    $page,
                    $language,
                    'subCategory',
                ),
                $this->slug(
                    $page,
                    $language,
                    'name',
                ),
            );

            if (
                null === $existing
                || $existing->getId() === $page->getId()
            ) {
                continue;
            }

            $form->get('category')->addError(new FormError($this->translator->trans(
                'Another page already answers to this address.',
                [],
                'validators',
            )));
        }
    }

    /**
     * The first path segments the application answers to itself, read from the router rather than kept as a list that
     * would go stale the moment a module is mounted somewhere new. A page under one of these would be unreachable,
     * since real routes are matched before the custom-page route ever sees the request.
     *
     * @return list<string>
     */
    private function reservedCategories(): array
    {
        if (null !== $this->reservedCategories) {
            return $this->reservedCategories;
        }

        $locales = array_map(
            static fn (Languages $language): string => $language->getLangParam(),
            Languages::cases(),
        );

        $reserved = [];
        foreach ($this->router->getRouteCollection() as $route) {
            $segments = explode(
                '/',
                trim(
                    $route->getPath(),
                    '/',
                ),
            );

            // The locale prefix is not part of the address a page could take.
            if (
                in_array(
                    $segments[0],
                    $locales,
                    true,
                )
            ) {
                array_shift($segments);
            }

            // A parameterised first segment (the custom-page route itself, the catch-all) reserves nothing.
            $segment = $segments[0] ?? '';
            if (
                '' === $segment
                || str_contains(
                    $segment,
                    '{',
                )
            ) {
                continue;
            }

            $reserved[$segment] = true;
        }

        return $this->reservedCategories = array_keys($reserved);
    }

    /**
     * One part of the address as written in this language, or null when it was left empty (which is how a page higher
     * up the tree says it has no sub-category or name).
     */
    private function slug(
        Page $page,
        Languages $language,
        string $part,
    ): ?string {
        $text = match ($part) {
            'category' => $page->getCategory(),
            'subCategory' => $page->getSubCategory(),
            default => $page->getName(),
        };

        $value = trim($text->getExactText($language) ?? '');

        return '' === $value
            ? null
            : $value;
    }
}
