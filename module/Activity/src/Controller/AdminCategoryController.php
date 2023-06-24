<?php

declare(strict_types=1);

namespace Activity\Controller;

use Activity\Service\AclService;
use Activity\Service\ActivityCategory as ActivityCategoryService;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;

/**
 * @method FlashMessenger flashMessenger()
 */
class AdminCategoryController extends AbstractActionController
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly ActivityCategoryService $categoryService,
    ) {
    }

    /**
     * View all Categories.
     */
    public function indexAction(): ViewModel
    {
        $categories = $this->categoryService->findAll();

        return new ViewModel(
            [
                'categories' => $categories,
            ],
        );
    }

    /**
     * Add Category.
     */
    public function addAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('addCategory', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to create an activity category'),
            );
        }

        /** @var Request $request */
        $request = $this->getRequest();
        $form = $this->categoryService->getCategoryForm();

        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                if ($this->categoryService->createCategory($form->getData())) {
                    $message = $this->translator->translate('The activity category was created successfully!');

                    return $this->redirectWithNotice(true, $message);
                }
            }
        }

        return new ViewModel(
            [
                'form' => $form,
                'action' => $this->translator->translate('Create Activity Category'),
            ],
        );
    }

    protected function redirectWithNotice(
        bool $success,
        string $message,
    ): Response {
        if ($success) {
            $this->flashMessenger()->addSuccessMessage($message);
        } else {
            $this->flashMessenger()->addErrorMessage($message);
        }

        return $this->redirect()->toRoute('activity_admin_categories');
    }

    /**
     * Delete Category.
     */
    public function deleteAction(): Response|ViewModel
    {
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $categoryId = (int) $this->params()->fromRoute('id');
            $category = $this->categoryService->getCategoryById($categoryId);

            if (null === $category) {
                return $this->notFoundAction();
            }

            $this->categoryService->deleteCategory($category);

            return $this->redirect()->toRoute('activity_admin_categories');
        }

        return $this->notFoundAction();
    }

    /**
     * Edit Category.
     */
    public function editAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('editCategory', 'activity')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to edit an activity category'),
            );
        }

        $categoryId = (int) $this->params()->fromRoute('id');
        $category = $this->categoryService->getCategoryById($categoryId);

        if (null === $category) {
            return $this->notFoundAction();
        }

        $form = $this->categoryService->getCategoryForm();
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                if ($this->categoryService->updateCategory($category, $form->getData())) {
                    $message = $this->translator->translate('The activity category was successfully updated!');

                    return $this->redirectWithNotice(true, $message);
                }
            }
        }

        $categoryData = $category->toArray();
        unset($categoryData['id']);

        $categoryData['language_dutch'] = null !== $categoryData['name'];
        $categoryData['language_english'] = null !== $categoryData['nameEn'];
        $form->setData($categoryData);

        $viewModel = new ViewModel(
            [
                'form' => $form,
                'action' => $this->translator->translate('Update Activity Category'),
            ],
        );
        $viewModel->setTemplate('activity/admin-category/add.phtml');

        return $viewModel;
    }
}
