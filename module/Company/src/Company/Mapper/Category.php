<?php

namespace Company\Mapper;

use Company\Model\JobCategory as CategoryModel;
use Doctrine\ORM\EntityManager;

/**
 * Mappers for cateogry.
 *
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
     *
     * @param EntityManager $em
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
     * Saves all categories
     *
     */
    public function save()
    {
        $this->em->flush();
    }

    /**
     * Finds the category with the given id
     *
     * @param integer $categorySlug
     */
    public function findCategory($categorySlug)
    {
        return $this->getRepository()->findOneBy(['slug' => $categorySlug]);
    }

    public function findVisibleCategoryByLanguage($categoryLanguage)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c');
        $qb->select('c')->where('c.language=:lang');
        $qb->andWhere('c.hidden=:hidden');
        $qb->setParameter('lang', $categoryLanguage);
        $qb->setParameter('hidden', false);
        $categories = $qb->getQuery()->getResult();

        return $categories;
    }

    public function createNullCategory($lang, $translator)
    {
        $categoryForJobsWithoutCategory =  new CategoryModel();
        $categoryForJobsWithoutCategory->setHidden(false);
        $categoryForJobsWithoutCategory->setLanguageNeutralId(null);
        $categoryForJobsWithoutCategory->setLanguage($lang);
        $categoryForJobsWithoutCategory->setSlug("jobs");
        $categoryForJobsWithoutCategory->setName($translator->translate("Job"));
        $categoryForJobsWithoutCategory->setPluralName($translator->translate("Jobs"));

        return $categoryForJobsWithoutCategory;
    }

    /**
     * Find the same category, but in the given language
     *
     */
    public function siblingCategory($category, $lang)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c');
        $qb->select('c')->where('c.languageNeutralId=:categoryId')->andWhere('c.language=:language');
        $qb->setParameter('categoryId', $category->getLanguageNeutralId());
        $qb->setParameter('language', $lang);
        $categories = $qb->getQuery()->getResult();
        return $categories[0];
    }

    public function findAllCategoriesById($categoryId)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c');
        $qb->select('c')->where('c.languageNeutralId=:categoryId');
        $qb->setParameter('categoryId', $categoryId);
        $categories = $qb->getQuery()->getResult();

        return $categories;
    }

    /**
     * Deletes the given category
     *
     * @param CategoryModel $category
     */
    public function delete($category)
    {
        $this->em->remove($category);
        $this->em->flush();
    }

    /**
     * Deletes the given category
     *
     * @param int $categoryId
     */
    public function deleteById($categoryId)
    {
        $category = $this->findEditableCategory($categoryId);
        if (is_null($category)) {
            return;
        }

        $this->delete($category);
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
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Company\Model\JobCategory');
    }
}
