<?php

namespace Activity\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container as SessionContainer;
use Zend\View\Model\ViewModel;

class AdminCategoryController extends AbstractActionController
{
    /**
     * View all Categories.
     */
    public function indexAction()
    {
        $categoryService = $this->getServiceLocator()->get('activity_service_category');
        $categories = $categoryService->getAllCategories();

        return ['categories' => $categories];
    }

    /**
     * Add Category.
     */
    public function addAction()
    {
        $categoryService = $this->getServiceLocator()->get('activity_service_category');
        $request = $this->getRequest();
        $translator = $this->getServiceLocator()->get('translator');

        if ($request->isPost()) {

            if ($categoryService->createCategory($request->getPost())) {
                $message = $translator->translate('The activity category was created successfully!');

                return $this->redirectWithNotice(true, $message);
            }
        }

        return [
            'form' => $categoryService->getCategoryForm(),
            'action' => $translator->translate('Create Activity Category'),
        ];
    }

    /**
    * Delete Category.
    */
    public function deleteAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $categoryId = (int) $this->params('id');
            $categoryService = $this->getServiceLocator()->get('activity_service_category');

            $category = $categoryService->getCategoryById($categoryId);
            if (is_null($category)) {
                return $this->notFoundAction();
            }

            $categoryService->deleteCategory($category);
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
        $categoryService = $this->getServiceLocator()->get('activity_service_category');

        $category = $categoryService->getCategoryById($categoryId);
        if (is_null($category)) {
            return $this->notFoundAction();
        }

        $categoryService = $this->getServiceLocator()->get('activity_service_category');
        $form = $categoryService->getCategoryForm();
        $request = $this->getRequest();
        $translator = $this->getServiceLocator()->get('translator');

        if ($request->isPost()) {
            if ($categoryService->updateCategory($category, $request->getPost())) {
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

    protected function redirectWithNotice($success, $message)
    {
        $categoryAdminSession = new SessionContainer('activityAdmin');
        $categoryAdminSession->success = $success;
        $categoryAdminSession->message = $message;

        return $this->redirect()->toRoute('activity_admin_categories');
    }
}
