<?php

declare(strict_types=1);

namespace Activity\Service;

use Activity\Form\ActivityCategory as ActivityCategoryForm;
use Activity\Mapper\ActivityCategory as ActivityCategoryMapper;
use Activity\Model\{
    ActivityCategory as ActivityCategoryModel,
    ActivityLocalisedText,
};
use Laminas\Mvc\I18n\Translator;
use User\Permissions\NotAllowedException;

class ActivityCategory
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly ActivityCategoryMapper $categoryMapper,
        private readonly ActivityCategoryForm $categoryForm,
    ) {
    }

    /**
     * Get all categories.
     *
     * @param int $id
     *
     * @return ActivityCategoryModel|null
     */
    public function getCategoryById(int $id): ?ActivityCategoryModel
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

    /**
     * @param array $data
     *
     * @return bool
     */
    public function createCategory(array $data): bool
    {
        $category = new ActivityCategoryModel();
        $category->setName(new ActivityLocalisedText($data['nameEn'], $data['name']));

        $this->categoryMapper->persist($category);

        return true;
    }

    /**
     * Return Category creation form.
     *
     * @return ActivityCategoryForm
     */
    public function getCategoryForm(): ActivityCategoryForm
    {
        if (!$this->aclService->isAllowed('addCategory', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to create an activity category')
            );
        }

        return $this->categoryForm;
    }

    /**
     * @param ActivityCategoryModel $category
     * @param array $data
     *
     * @return bool
     */
    public function updateCategory(
        ActivityCategoryModel $category,
        array $data,
    ): bool {
        $name = $category->getName();
        $name->updatevalues($data['nameEn'], $data['name']);

        $this->categoryMapper->persist($name);
        $this->categoryMapper->persist($category);

        return true;
    }

    /**
     * @param ActivityCategoryModel $category
     */
    public function deleteCategory(ActivityCategoryModel $category): void
    {
        if (!$this->aclService->isAllowed('deleteCategory', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to delete an activity category')
            );
        }

        $this->categoryMapper->remove($category);
    }
}
