<?php

declare(strict_types=1);

namespace Frontpage\Mapper;

use Application\Mapper\BaseMapper;
use Application\Model\Enums\Languages;
use Doctrine\ORM\Query\Expr\Join;
use Frontpage\Model\Page as PageModel;

use function mb_strtoupper;

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
        Languages $language,
        string $category,
        ?string $subCategory = null,
        ?string $name = null,
    ): ?PageModel {
        $queryLanguage = mb_strtoupper($language->getLangParam());

        $qb = $this->getRepository()->createQueryBuilder('p');
        $qb->innerJoin(
            'p.category',
            'c',
            Join::WITH,
            'c.value' . $queryLanguage . ' = :category',
        )->setParameter('category', $category);

        if (null !== $subCategory) {
            $subCategoryExpression = 's.value' . $queryLanguage . ' = :subCategory';
            $qb->setParameter('subCategory', $subCategory);
        } else {
            $subCategoryExpression = 's.value' . $queryLanguage . ' IS NULL';
        }

        $qb->innerJoin(
            'p.subCategory',
            's',
            Join::WITH,
            $subCategoryExpression,
        );

        if (null !== $name) {
            $nameExpression = 'n.value' . $queryLanguage . ' = :name';
            $qb->setParameter('name', $name);
        } else {
            $nameExpression = 'n.value' . $queryLanguage . ' IS NULL';
        }

        $qb->innerJoin(
            'p.name',
            'n',
            Join::WITH,
            $nameExpression,
        );

        return $qb->getQuery()->getOneOrNullResult();
    }

    protected function getRepositoryName(): string
    {
        return PageModel::class;
    }
}
