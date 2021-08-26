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
     * @param string $subCategory
     * @param string $name
     *
     * @return PageModel|null
     */
    public function findPage($category, $subCategory = null, $name = null)
    {
        return $this->getRepository()->findOneBy(
            [
                'category' => $category,
                'subCategory' => $subCategory,
                'name' => $name,
            ]
        );
    }

    protected function getRepositoryName(): string
    {
        return PageModel::class;
    }
}
