<?php

declare(strict_types=1);

namespace App\Service\Frontpage;

use App\Entity\Application\Enums\Languages;
use App\Entity\Frontpage\Page as PageModel;
use App\Repository\Frontpage\PageRepository;

class PageService
{
    public function __construct(private readonly PageRepository $pageRepository)
    {
    }

    /**
     * Returns a single page.
     */
    public function getPage(
        Languages $language,
        string $category,
        ?string $subCategory = null,
        ?string $name = null,
    ): ?PageModel {
        return $this->pageRepository->findPage(
            $language,
            $category,
            $subCategory,
            $name,
        );
    }

    /**
     * Returns the parent pages of a page if those exist.
     *
     * @return array<array-key, PageModel|null>
     */
    public function getPageParents(
        PageModel $page,
        Languages $language,
    ): array {
        $parents = [];

        if (null !== $page->getSubCategory()->getExactText($language)) {
            /** @psalm-suppress PossiblyNullArgument */
            $parents[] = $this->pageRepository->findPage(
                $language,
                $page->getCategory()->getExactText($language),
            );

            /** @psalm-suppress PossiblyNullArgument */
            if (null !== $page->getName()->getExactText($language)) {
                $parents[] = $this->pageRepository->findPage(
                    $language,
                    $page->getCategory()->getExactText($language),
                    $page->getSubCategory()->getExactText($language),
                );
            }
        }

        return $parents;
    }
}
