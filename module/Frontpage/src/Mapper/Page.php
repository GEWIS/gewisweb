<?php

declare(strict_types=1);

namespace Frontpage\Mapper;

use Application\Mapper\BaseMapper;
use Frontpage\Model\Page as PageModel;

/**
 * Mappers for Pages.
 *
 * @template-extends BaseMapper<PageModel>
 */
class Page extends BaseMapper
{
    /**
     * Returns a page.
     */
    public function findPage(
        string $category,
        ?string $subCategory = null,
        ?string $name = null,
    ): ?PageModel {
        return $this->getRepository()->findOneBy(
            [
                'category' => $category,
                'subCategory' => $subCategory,
                'name' => $name,
            ],
        );
    }

    protected function getRepositoryName(): string
    {
        return PageModel::class;
    }
}
