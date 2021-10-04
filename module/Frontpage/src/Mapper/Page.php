<?php

namespace Frontpage\Mapper;

use Application\Mapper\BaseMapper;
use Frontpage\Model\Page as PageModel;

/**
 * Mappers for Pages.
 */
class Page extends BaseMapper
{
    /**
     * Returns a page.
     *
     * @param string $category
     * @param string|null $subCategory
     * @param string|null $name
     *
     * @return PageModel|null
     */
    public function findPage(string $category, ?string $subCategory = null, ?string $name = null): ?PageModel
    {
        return $this->getRepository()->findOneBy(
            [
                'category' => $category,
                'subCategory' => $subCategory,
                'name' => $name,
            ]
        );
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return PageModel::class;
    }
}
