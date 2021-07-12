<?php

namespace Activity\Controller;

use Activity\Service\ActivityCategory;
use Laminas\Mvc\I18n\Translator;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Session\Container as SessionContainer;
use Laminas\View\Model\ViewModel;

class AdminCategoryController extends AbstractActionController
{

    /**
     * @var ActivityCategory
     */
    private $categoryService;
    private Translator $translator;

    public function __construct(Translator $translator, ActivityCategory $categoryService)
    {
        $this->categoryService = $categoryService;
        $this->translator = $translator;
    }

    /**
     * View all Categories.
     */
    public function indexAction()
    {
        $categories = $this->categoryService->getAllCategories();

        return ['categories' => $categories];
    }

    /**
     * Add Category.
     */
    public function addAction()
    {
        $request = $this->getRequest();
        $translator = $this->translator;

        if ($request->isPost()) {
            if ($this->categoryService->createCategory($request->getPost())) {
                $message = $translator->translate('The activity category was created successfully!');

                return $this->redirectWithNotice(true, $message);
            }
        }

        return [
            'form' => $this->categoryService->getCategoryForm(),
            'action' => $translator->translate('Create Activity Category'),
        ];
    }

    protected function redirectWithNotice($success, $message)
    {
        $categoryAdminSession = new SessionContainer('activityAdmin');
        $categoryAdminSession->success = $success;
        $categoryAdminSession->message = $message;

        return $this->redirect()->toRoute('activity_admin_categories');
    }

    /**
     * Delete Category.
     */
    public function deleteAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $categoryId = (int) $this->params('id');

            $category = $this->categoryService->getCategoryById($categoryId);
            if (is_null($category)) {
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
        $categoryId = (int) $this->params('id');

        $category = $this->categoryService->getCategoryById($categoryId);
        if (is_null($category)) {
            return $this->notFoundAction();
        }

        $form = $this->categoryService->getCategoryForm();
        $request = $this->getRequest();
        $translator = $this->translator;

        if ($request->isPost()) {
            if ($this->categoryService->updateCategory($category, $request->getPost())) {
                $message = $translator->translate('The activity category was successfully updated!');

                return $this->redirectWithNotice(true, $message);
            }
        }

        $categoryData = $category->toArray();
        unset($categoryData['id'], $categoryData['activities']);

        $categoryData['language_dutch'] = !is_null($categoryData['name']);
        $categoryData['language_english'] = !is_null($categoryData['nameEn']);
        $form->setData($categoryData);

        $viewModel = new ViewModel(['form' => $form, 'action' => $translator->translate('Update Activity Category')]);
        $viewModel->setTemplate('activity/admin-category/add.phtml');

        return $viewModel;
    }
}
