<?php

namespace Company\Mapper;

use Company\Model\JobCategory as CategoryModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

/**
 * Mappers for cateogry.
 */
class Category
{
    /**
     * Doctrine entity manager.
     *
     * @var EntityManager
     */
    protected $em;

    /**
     * Constructor.
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function persist($label)
    {
        $this->em->persist($label);
        $this->em->flush();
    }

    /**
     * Saves all categories.
     */
    public function save()
    {
        $this->em->flush();
    }

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
     * Deletes the given category.
     *
     * @param CategoryModel $category
     */
    public function delete($category)
    {
        $this->em->remove($category);
        $this->em->flush();
    }

    /**
     * Find all Categories.
     *
     * @return array
     */
    public function findAll()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Company\Model\JobCategory');
    }
}
