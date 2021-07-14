<?php

namespace Activity\Service;

use Activity\Form\ActivityCategory as CategoryForm;
use Activity\Model\ActivityCategory as CategoryModel;
use Activity\Model\LocalisedText;
use Application\Service\AbstractAclService;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Laminas\Mvc\I18n\Translator;
use Laminas\Permissions\Acl\Acl;
use User\Model\User;
use User\Permissions\NotAllowedException;

class ActivityCategory extends AbstractAclService
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var User|string
     */
    private $userRole;

    /**
     * @var Acl
     */
    private $acl;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var \Activity\Mapper\ActivityCategory
     */
    private $categoryMapper;

    /**
     * @var CategoryForm
     */
    private $categoryForm;

    public function __construct(
        Translator $translator,
        $userRole,
        Acl $acl,
        EntityManager $entityManager,
        \Activity\Mapper\ActivityCategory $categoryMapper,
        CategoryForm $categoryForm
    ) {
        $this->translator = $translator;
        $this->userRole = $userRole;
        $this->acl = $acl;
        $this->entityManager = $entityManager;
        $this->categoryMapper = $categoryMapper;
        $this->categoryForm = $categoryForm;
    }

    public function getRole()
    {
        return $this->userRole;
    }

    /**
     * Get the ACL.
     *
     * @return Acl
     */
    public function getAcl()
    {
        return $this->acl;
    }

    /**
     * Get all categories.
     *
     * @param int $id
     *
     * @return CategoryModel
     */
    public function getCategoryById($id)
    {
        if (!$this->isAllowed('listCategories', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view activity categories')
            );
        }

        return $this->categoryMapper->getCategoryById($id);
    }

    /**
     * Get all categories.
     *
     * @return Collection
     */
    public function getAllCategories()
    {
        if (!$this->isAllowed('listCategories', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view activity categories')
            );
        }

        return $this->categoryMapper->getAllCategories();
    }

    public function createCategory($data)
    {
        if (!$this->isAllowed('addCategory', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to create an activity category')
            );
        }

        $form = $this->getCategoryForm();
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $category = new CategoryModel();
        $category->setName(new LocalisedText($data['nameEn'], $data['name']));

        $em = $this->entityManager;
        $em->persist($category);
        $em->flush();

        return true;
    }

    /**
     * Return Category creation form.
     *
     * @return CategoryForm
     */
    public function getCategoryForm()
    {
        if (!$this->isAllowed('addCategory', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to create an activity category')
            );
        }

        return $this->categoryForm;
    }

    public function updateCategory($category, $data)
    {
        if (!$this->isAllowed('editCategory', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to edit an activity category')
            );
        }

        $form = $this->getCategoryForm();
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $name = $category->getName();
        $name->updatevalues($data['nameEn'], $data['name']);

        $em = $this->entityManager;
        $em->persist($name);
        $em->persist($category);
        $em->flush();

        return true;
    }

    public function deleteCategory($category)
    {
        if (!$this->isAllowed('deleteCategory', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to delete an activity category')
            );
        }

        $em = $this->entityManager;
        $em->remove($category);
        $em->flush();
    }

    /**
     * Get the default resource ID.
     *
     * This is used by {@link isAllowed()} when no resource is specified.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'activity';
    }
}
