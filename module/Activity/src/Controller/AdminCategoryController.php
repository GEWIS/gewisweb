<?php

namespace Activity\Controller;

use Activity\Service\ActivityCategory as ActivityCategoryService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\Session\Container as SessionContainer;
use Laminas\View\Model\ViewModel;

class AdminCategoryController extends AbstractActionController
{
    /**
     * @var ActivityCategoryService
     */
    private ActivityCategoryService $categoryService;

    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * AdminCategoryController constructor.
     *
     * @param ActivityCategoryService $categoryService
     * @param Translator $translator
     */
    public function __construct(
        ActivityCategoryService $categoryService,
        Translator              $translator
    )
    {
        $this->categoryService = $categoryService;
        $this->translator = $translator;
    }

    /**
     * View all Categories.
     */
    public function indexAction()
    {
        $categories = $this->categoryService->findAll();

        return new ViewModel(
            [
                'categories' => $categories,
            ]
        );
    }

    /**
     * Add Category.
     */
    public function addAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            if ($this->categoryService->createCategory($request->getPost())) {
                $message = $this->translator->translate('The activity category was created successfully!');

                return $this->redirectWithNotice(true, $message);
            }
        }

        return new ViewModel(
            [
                'form' => $this->categoryService->getCategoryForm(),
                'action' => $this->translator->translate('Create Activity Category'),
            ]
        );
    }

    protected function redirectWithNotice($success, $message)
    {
        if ($success) {
            $this->plugin('FlashMessenger')->addSuccessMessage($message);
        } else {
            $this->plugin('FlashMessenger')->addErrorMessage($message);
        }

        return $this->redirect()->toRoute('activity_admin_categories');
    }

    /**
     * Delete Category.
     */
    public function deleteAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $categoryId = (int)$this->params('id');
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
    public function editAction()
    {
        $categoryId = (int)$this->params('id');
        $category = $this->categoryService->getCategoryById($categoryId);

        if (null === $category) {
            return $this->notFoundAction();
        }

        $form = $this->categoryService->getCategoryForm();
        $request = $this->getRequest();

        if ($request->isPost()) {
            if ($this->categoryService->updateCategory($category, $request->getPost())) {
                $message = $this->translator->translate('The activity category was successfully updated!');

                return $this->redirectWithNotice(true, $message);
            }
        }

        $categoryData = $category->toArray();
        unset($categoryData['id'], $categoryData['activities']);

        $categoryData['language_dutch'] = null !== $categoryData['name'];
        $categoryData['language_english'] = null !== $categoryData['nameEn'];
        $form->setData($categoryData);

        $viewModel = new ViewModel(
            [
                'form' => $form,
                'action' => $this->translator->translate('Update Activity Category')
            ]
        );
        $viewModel->setTemplate('activity/admin-category/add.phtml');

        return $viewModel;
    }
}
