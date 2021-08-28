<?php

namespace Company\Mapper;

use Application\Mapper\BaseMapper;
use Company\Model\JobCategory;
use Company\Model\JobCategory as CategoryModel;

/**
 * Mappers for cateogry.
 */
class Category extends BaseMapper
{
    /**
     * Finds the category with the given id.
     *
     * @param int $categorySlug
     */
    public function findCategory($categorySlug)
    {
        return $this->getRepository()->findOneBy(['slug' => $categorySlug]);
    }

    public function findVisibleCategoryByLanguage($categoryLanguage)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c')
            ->select('c')->where('c.language=:lang')
            ->andWhere('c.hidden=:hidden')
            ->setParameter('lang', $categoryLanguage)
            ->setParameter('hidden', false);

        return $qb->getQuery()->getResult();
    }

    public function createNullCategory($lang, $translator)
    {
        $categoryForJobsWithoutCategory = new CategoryModel();
        $categoryForJobsWithoutCategory->setHidden(false);
        $categoryForJobsWithoutCategory->setLanguageNeutralId(null);
        $categoryForJobsWithoutCategory->setLanguage($lang);
        $categoryForJobsWithoutCategory->setSlug('jobs');
        $categoryForJobsWithoutCategory->setName($translator->translate('Job'));
        $categoryForJobsWithoutCategory->setPluralName($translator->translate('Jobs'));

        return $categoryForJobsWithoutCategory;
    }

    /**
     * Find the same category, but in the given language.
     */
    public function siblingCategory($category, $lang)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c')
            ->select('c')->where('c.languageNeutralId=:categoryId')->andWhere('c.language=:language')
            ->setParameter('categoryId', $category->getLanguageNeutralId())
            ->setParameter('language', $lang);

        $categories = $qb->getQuery()->getResult();

        return $categories[0];
    }

    public function findAllCategoriesById($categoryId)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c')
            ->select('c')->where('c.languageNeutralId=:categoryId')
            ->setParameter('categoryId', $categoryId);

        return $qb->getQuery()->getResult();
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return JobCategory::class;
    }
}
