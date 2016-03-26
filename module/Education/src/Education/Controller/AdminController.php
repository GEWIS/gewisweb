<?php

namespace Education\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class AdminController extends AbstractActionController {

    public function indexAction()
    {
    }

    public function bulkAction()
    {
        $service = $this->getExamService();
        $request = $this->getRequest();

        if ($request->isPost()) {
            // try uploading
            if ($service->tempUpload($request->getPost(), $request->getFiles())) {
                return new ViewModel([
                    'success' => true
                ]);
            } else {
                $this->getResponse()->setStatusCode(500);
                return new ViewModel([
                    'success' => false
                ]);
            }
        }

        return new ViewModel([
            'form' => $service->getTempUploadForm()
        ]);
    }

    /**
     * Edit several exams in bulk.
     */
    public function editExamAction()
    {
        $service = $this->getExamService();
        $request = $this->getRequest();

        if ($request->isPost() && $service->bulkEdit($request->getPost())) {
            return new ViewModel([
                'success' => true
            ]);
        }

        $config = $this->getServiceLocator()->get('config');
        $config = $config['education_temp'];

        return new ViewModel([
            'form'   => $service->getBulkExamForm(),
            'config' => $config
        ]);
    }

    /**
     * Edit summaries in bulk.
     */
    public function editSummaryAction()
    {
        $service = $this->getExamService();
        $request = $this->getRequest();

        if ($request->isPost() && $service->bulkEdit($request->getPost())) {
            return new ViewModel([
                'success' => true
            ]);
        }

        $config = $this->getServiceLocator()->get('config');
        $config = $config['education_temp'];

        return new ViewModel([
            'form'   => $service->getBulkSummaryForm(),
            'config' => $config
        ]);
    }

    public function summaryAction()
    {
        $service = $this->getExamService();
        $request = $this->getRequest();

        if ($request->isPost()) {
            // try uploading
            if ($service->uploadSummary($request->getPost(), $request->getFiles())) {
                return new ViewModel([
                    'success' => true
                ]);
            }
        }

        return new ViewModel([
            'form' => $service->getSummaryUploadForm()
        ]);
    }

    /**
     * Get the exam service.
     */
    public function getExamService()
    {
        return $this->getServiceLocator()->get('education_service_exam');
    }
}
