<?php

namespace Activity\Service;

use Activity\Form\ActivityCategory as CategoryForm;
use Activity\Model\ActivityCategory as CategoryModel;
use Activity\Model\ActivityLocalisedText;
use Doctrine\ORM\EntityManager;
use Laminas\Mvc\I18n\Translator;
use User\Permissions\NotAllowedException;

class ActivityCategory
{
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

    /**
     * @var AclService
     */
    private AclService $aclService;

    /**
     * @var Translator
     */
    private Translator $translator;

    public function __construct(
        EntityManager $entityManager,
        \Activity\Mapper\ActivityCategory $categoryMapper,
        CategoryForm $categoryForm,
        AclService $aclService,
        Translator $translator,
    ) {
        $this->entityManager = $entityManager;
        $this->categoryMapper = $categoryMapper;
        $this->categoryForm = $categoryForm;
        $this->aclService = $aclService;
        $this->translator = $translator;
    }

    /**
     * Get all categories.
     *
     * @param int $id
     *
     * @return CategoryModel|null
     */
    public function getCategoryById(int $id): ?CategoryModel
    {
        if (!$this->aclService->isAllowed('listCategories', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view activity categories')
            );
        }

        return $this->categoryMapper->find($id);
    }

    /**
     * Get all categories.
     *
     * @return array
     */
    public function findAll(): array
    {
        if (!$this->aclService->isAllowed('listCategories', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view activity categories')
            );
        }

        return $this->categoryMapper->findAll();
    }

    public function createCategory($data)
    {
        if (!$this->aclService->isAllowed('addCategory', 'activity')) {
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
        $category->setName(new ActivityLocalisedText($data['nameEn'], $data['name']));

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
        if (!$this->aclService->isAllowed('addCategory', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to create an activity category')
            );
        }

        return $this->categoryForm;
    }

    public function updateCategory($category, $data)
    {
        if (!$this->aclService->isAllowed('editCategory', 'activity')) {
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
        if (!$this->aclService->isAllowed('deleteCategory', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to delete an activity category')
            );
        }

        $em = $this->entityManager;
        $em->remove($category);
        $em->flush();
    }
}
