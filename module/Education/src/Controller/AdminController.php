<?php

namespace Education\Controller;

use Education\Service\{
    AclService,
    Exam as ExamService,
};
use Education\Model\Course as CourseModel;
use Laminas\Http\{
    Request,
    Response,
};
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\{
    JsonModel,
    ViewModel,
};
use User\Permissions\NotAllowedException;

class AdminController extends AbstractActionController
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly ExamService $examService,
        private readonly array $educationTempConfig,
    ) {
    }

    public function indexAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('admin', 'education')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to administer education settings')
            );
        }

        return new ViewModel();
    }

    public function courseAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('edit', 'course')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to administer courses'));
        }

        return new ViewModel(['courses' => $this->examService->getAllCourses()]);
    }

    public function addCourseAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('add', 'course')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to add courses'));
        }

        /** @var Request $request */
        $request = $this->getRequest();
        $form = $this->examService->getCourseForm();

        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                /** @var CourseModel $course */
                $course = $form->getObject();

                if ($this->examService->saveCourse($course)) {
                    $this->plugin('FlashMessenger')->addSuccessMessage(
                        $this->translator->translate('Successfully added course!')
                    );

                    return $this->redirect()->toRoute('admin_education/course/edit', ['course' => $course->getCode()]);
                } else {
                    $this->plugin('FlashMessenger')->addErrorMessage(
                        $this->translator->translate('An error occurred while saving the course!')
                    );
                }
            }
        }

        return new ViewModel(
            [
                'form' => $form,
            ]
        );
    }

    public function editCourseAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('edit', 'course')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit courses'));
        }

        $courseId = $this->params()->fromRoute('course');
        $course = $this->examService->getCourse($courseId);

        if (null === $course) {
            return $this->notFoundAction();
        }

        /** @var Request $request */
        $request = $this->getRequest();
        $form = $this->examService->getCourseForm($course);

        if ($request->isPost()) {
             $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                /** @var CourseModel $course */
                $course = $form->getObject();

                if ($this->examService->saveCourse($course)) {
                    $this->plugin('FlashMessenger')->addSuccessMessage(
                        $this->translator->translate('Successfully updated course information!')
                    );
                } else {
                    $this->plugin('FlashMessenger')->addErrorMessage(
                        $this->translator->translate('An error occurred while saving the course!')
                    );
                }
            }
        }

        return new ViewModel([
            'course' => $course,
            'form' => $form,
        ]);
    }

    public function deleteCourseAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('delete', 'course')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to delete courses'));
        }

        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->getPost()) {
            $courseId = $this->params()->fromRoute('course');

            if (null !== ($course = $this->examService->getCourse($courseId))) {
                $this->examService->deleteCourse($course);

                return $this->redirect()->toRoute('admin_education/course');
            }
        }

        return $this->notFoundAction();
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
