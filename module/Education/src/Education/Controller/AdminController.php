<?php

namespace Education\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class AdminController extends AbstractActionController {

    public function indexAction()
    {
    }

    public function addCourseAction()
    {
        $service = $this->getExamService();
        $request = $this->getRequest();

        if ($request->isPost()) {
            // try uploading
            if ($service->addCourse($request->getPost())) {
                $this->getResponse()->setStatusCode(200);
                return new ViewModel([
                    'form' => $service->getAddCourseForm(),
                    'success' => true
                ]);
            }
            $this->getResponse()->setStatusCode(400);
            return new ViewModel([
                'form' => $service->getAddCourseForm(),
                'success' => false
            ]);
        }

        return new ViewModel([
            'form' => $service->getAddCourseForm()
        ]);
    }

    public function bulkExamAction()
    {
        $service = $this->getExamService();
        $request = $this->getRequest();

        if ($request->isPost()) {
            // try uploading
            if ($service->tempExamUpload($request->getPost(), $request->getFiles())) {
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

    public function bulkSummaryAction()
    {
        $service = $this->getExamService();
        $request = $this->getRequest();

        if ($request->isPost()) {
            // try uploading
            if ($service->tempSummaryUpload($request->getPost(), $request->getFiles())) {
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

        if ($request->isPost() && $service->bulkExamEdit($request->getPost())) {
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

        if ($request->isPost() && $service->bulkSummaryEdit($request->getPost())) {
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

    public function deleteTempAction()
    {
        $service = $this->getExamService();
        $request = $this->getRequest();
        if ($request->isPost()) {
            $service->deleteTempExam($this->params()->fromRoute('filename'), $this->params()->fromRoute('type'));
            return new JsonModel(['success' => 'true']);
        }
        return $this->notFoundAction();
    }

    /**
     * Get the exam service.
     */
    public function getExamService()
    {
        return $this->getServiceLocator()->get('education_service_exam');
    }
}
