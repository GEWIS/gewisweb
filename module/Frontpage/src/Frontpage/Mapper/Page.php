<?php

namespace Frontpage\Mapper;

use Doctrine\ORM\EntityManager;

/**
 * Mappers for Pages.
 *
 */
class Page
{
    /**
     * Doctrine entity manager.
     *
     * @var EntityManager
     */
    protected $em;

    /**
     * Constructor
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Returns a page.
     *
     * @param string $category
     * @param string $subCategory
     * @param string $name
     * @return \Frontpage\Model\Page|null
     */
    public function findPage($category, $subCategory = null, $name = null)
    {
        return $this->getRepository()->findOneBy([
            'category' => $category,
            'subCategory' => $subCategory,
            'name' => $name
        ]);
    }

    /**
     * Returns a page based on its id.
     *
     * @param integer $pageId
     * @return \Frontpage\Model\Page|null
     */
    public function findPageById($pageId)
    {
        return $this->getRepository()->find($pageId);
    }

    /**
     * Returns all available pages.
     */
    public function getAllPages()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Removes a page.
     *
     * @param \Frontpage\Model\Page $page
     */
    public function remove($page)
    {
        $this->em->remove($page);
    }

    /**
     * Persist a page.
     *
     * @param \Frontpage\Model\Page $page
     */
    public function persist($page)
    {
        $this->em->persist($page);
    }

    /**
     * Flush.
     */
    public function flush()
    {
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Frontpage\Model\Page');
    }
}
