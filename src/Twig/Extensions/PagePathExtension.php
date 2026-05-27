<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use App\Entity\Application\Enums\Languages;
use App\Entity\Frontpage\Page;
use Locale;
use Override;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @psalm-import-type LangParam from Languages
 */
class PagePathExtension extends AbstractExtension
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'page_path',
                $this->generatePagePath(...),
            ),
            new TwigFunction(
                'localised_page_path',
                $this->generateLocalisedPagePath(...),
            ),
        ];
    }

    public function generatePagePath(
        string $category,
        string $categoryEn,
        ?string $subCategory = null,
        ?string $subCategoryEn = null,
        ?string $name = null,
        ?string $nameEn = null,
    ): string {
        $isEn = 'en' === Locale::getDefault();

        $params = [
            'category' => $isEn ? $categoryEn : $category,
        ];

        if (null !== $subCategory) {
            $params['subCategory'] = $isEn
                ? $subCategoryEn
                : $subCategory;
        }

        if (null !== $name) {
            $params['name'] = $isEn
                ? $nameEn
                : $name;
        }

        return $this->urlGenerator->generate(
            'page_route',
            $params,
        );
    }

    /**
     * @param LangParam $locale
     */
    public function generateLocalisedPagePath(
        Page $page,
        string $locale,
    ): string {
        $locale = Languages::fromLangParam($locale);

        $params = [
            '_locale' => $locale->getLangParam(),
            'category' => $page->getCategory()->getExactText($locale),
            'subCategory' => $page->getSubCategory()->getExactText($locale),
            'name' => $page->getName()->getExactText($locale),
        ];

        return $this->urlGenerator->generate(
            'page_route',
            $params,
        );
    }
}
