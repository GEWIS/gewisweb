<?php

namespace Education\Controller;

use Education\Service\Exam as ExamService;
use Laminas\Http\Request;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\{
    JsonModel,
    ViewModel,
};

class AdminController extends AbstractActionController
{
    public function __construct(
        private readonly ExamService $examService,
        private readonly array $educationTempConfig,
    ) {
    }

    public function indexAction(): ViewModel
    {
        return new ViewModel();
    }

    public function addCourseAction(): ViewModel
    {
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            // try uploading
            if ($this->examService->addCourse($request->getPost()->toArray())) {
                $this->getResponse()->setStatusCode(200);

                return new ViewModel(
                    [
                        'form' => $this->examService->getAddCourseForm(),
                        'success' => true,
                    ]
                );
            }
            $this->getResponse()->setStatusCode(400);

            return new ViewModel(
                [
                    'form' => $this->examService->getAddCourseForm(),
                    'success' => false,
                ]
            );
        }

        return new ViewModel(
            [
                'form' => $this->examService->getAddCourseForm(),
            ]
        );
    }

    public function bulkExamAction(): ViewModel
    {
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            // try uploading
            if ($this->examService->tempExamUpload($request->getPost(), $request->getFiles())) {
                return new ViewModel(
                    [
                        'success' => true,
                    ]
                );
            } else {
                $this->getResponse()->setStatusCode(500);

                return new ViewModel(
                    [
                        'success' => false,
                    ]
                );
            }
        }

        return new ViewModel(
            [
                'form' => $this->examService->getTempUploadForm(),
            ]
        );
    }

    public function bulkSummaryAction(): ViewModel
    {
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            // try uploading
            if ($this->examService->tempSummaryUpload($request->getPost(), $request->getFiles())) {
                return new ViewModel(
                    [
                        'success' => true,
                    ]
                );
            } else {
                $this->getResponse()->setStatusCode(500);

                return new ViewModel(
                    [
                        'success' => false,
                    ]
                );
            }
        }

        return new ViewModel(
            [
                'form' => $this->examService->getTempUploadForm(),
            ]
        );
    }

    /**
     * Edit several exams in bulk.
     */
    public function editExamAction(): ViewModel
    {
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost() && $this->examService->bulkExamEdit($request->getPost()->toArray())) {
            return new ViewModel(
                [
                    'success' => true,
                ]
            );
        }

        return new ViewModel(
            [
                'form' => $this->examService->getBulkExamForm(),
                'config' => $this->educationTempConfig,
            ]
        );
    }

    /**
     * Edit summaries in bulk.
     */
    public function editSummaryAction(): ViewModel
    {
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost() && $this->examService->bulkSummaryEdit($request->getPost()->toArray())) {
            return new ViewModel(
                [
                    'success' => true,
                ]
            );
        }

        $config = $this->educationTempConfig;

        return new ViewModel(
            [
                'form' => $this->examService->getBulkSummaryForm(),
                'config' => $config,
            ]
        );
    }

    public function deleteTempAction(): JsonModel|ViewModel
    {
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $this->examService->deleteTempExam(
                $this->params()->fromRoute('filename'),
                $this->params()->fromRoute('type')
            );

            return new JsonModel(['success' => 'true']);
        }

        return $this->notFoundAction();
    }
}
