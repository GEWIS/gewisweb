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
     * @param integer $packageID
     */
    public function findCategory($categorySlug)
    {
        return $this->getRepository()->findOneBy(['slug' => $categorySlug]);
    }

    /**
     * Inserts a new package into the given company
     *
     */
    public function insert($lang, $id, $category = null)
    {
        if ($category == null) {
            $category = new CategoryModel();
        }
        $category->setLanguage($lang);
        $category->setLanguageNeutralId($id);
        $category->setHidden(false);
        $this->em->persist($category);
        $this->em->flush();
        if ($id == -1) {
            $id = $category->getId();
        }
        $category->setLanguageNeutralId($id);

        return $category;
    }

    public function findAllCategoriesById($categoryId)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c');
        $qb->select('c')->where('c.languageNeutralId=:categoryID');
        $qb->setParameter('categoryID', $categoryId);
        $categories = $qb->getQuery()->getResult();

        return $categories;
    }
    /**
     * Deletes the given category
     *
     */
    public function delete($categoryID)
    {
        $category = $this->findEditableCategory($categoryID);
        if (is_null($category)) {
            return;
        }

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

    private function createPackage($type)
    {
        return new JobCategory ($this->em);
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
