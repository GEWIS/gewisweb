<?php

declare(strict_types=1);

namespace Education\Controller;

use Education\Model\Exam as ExamModel;
use Education\Model\Summary as SummaryModel;
use Education\Service\AclService;
use Education\Service\Course as CourseService;
use Laminas\Form\FormInterface;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use Override;
use User\Permissions\NotAllowedException;

/**
 * @method FlashMessenger flashMessenger()
 */
class AdminController extends AbstractActionController
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly CourseService $courseService,
        private readonly array $educationTempConfig,
    ) {
    }

    #[Override]
    public function indexAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('admin', 'education')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to administer education settings'),
            );
        }

        return new ViewModel();
    }

    public function courseAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('edit', 'course')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to administer courses'));
        }

        return new ViewModel(['courses' => $this->courseService->getAllCourses()]);
    }

    public function addCourseAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('add', 'course')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to add courses'));
        }

        /** @var Request $request */
        $request = $this->getRequest();
        $form = $this->courseService->getCourseForm();

        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                $data = $form->getData(FormInterface::VALUES_AS_ARRAY);
                $course = $this->courseService->saveCourse($data);

                $this->flashMessenger()->addSuccessMessage(
                    $this->translator->translate('Successfully added course!'),
                );

                return $this->redirect()->toRoute('admin_education/course/edit', ['course' => $course->getCode()]);
            }

            $this->flashMessenger()->addErrorMessage(
                $this->translator->translate('The course form is invalid!'),
            );
        }

        return new ViewModel(
            [
                'form' => $form,
            ],
        );
    }

    public function editCourseAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('edit', 'course')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit courses'));
        }

        $courseId = $this->params()->fromRoute('course');
        $course = $this->courseService->getCourse($courseId);

        if (null === $course) {
            return $this->notFoundAction();
        }

        /** @var Request $request */
        $request = $this->getRequest();
        $form = $this->courseService->getCourseForm($course);

        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                $data = $form->getData(FormInterface::VALUES_AS_ARRAY);
                $course = $this->courseService->updateCourse($course, $data);

                $this->flashMessenger()->addSuccessMessage(
                    $this->translator->translate('Successfully updated course information!'),
                );
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

            if (null !== ($course = $this->courseService->getCourse($courseId))) {
                $this->courseService->deleteCourse($course);

                return $this->redirect()->toRoute('admin_education/course');
            }
        }

        return $this->notFoundAction();
    }

    public function courseDocumentsAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('edit', 'course')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit courses'));
        }

        $courseId = $this->params()->fromRoute('course');
        $course = $this->courseService->getCourse($courseId);

        if (null === $course) {
            return $this->notFoundAction();
        }

        return new ViewModel([
            'course' => $course,
            'exams' => $this->courseService->getDocumentsForCourse($course, ExamModel::class),
            'summaries' => $this->courseService->getDocumentsForCourse($course, SummaryModel::class),
        ]);
    }

    public function deleteCourseDocumentAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('delete', 'course_document')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to delete course documents'),
            );
        }

        $courseId = $this->params()->fromRoute('course');
        $course = $this->courseService->getCourse($courseId);

        if (null === $course) {
            return $this->notFoundAction();
        }

        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->getPost()) {
            $documentId = (int) $this->params()->fromRoute('document');

            if (null !== ($document = $this->courseService->getDocument($documentId))) {
                $this->courseService->deleteDocument($document);

                return $this->redirect()->toRoute('admin_education/course/documents', ['course' => $course->getCode()]);
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
            if ($this->courseService->tempExamUpload($request->getPost(), $request->getFiles())) {
                return new ViewModel(
                    [
                        'success' => true,
                    ],
                );
            }

            $this->getResponse()->setStatusCode(500);

            return new ViewModel(
                [
                    'success' => false,
                ],
            );
        }

        return new ViewModel(
            [
                'form' => $this->courseService->getTempUploadForm(),
            ],
        );
    }

    public function bulkSummaryAction(): ViewModel
    {
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            // try uploading
            if ($this->courseService->tempSummaryUpload($request->getPost(), $request->getFiles())) {
                return new ViewModel(
                    [
                        'success' => true,
                    ],
                );
            }

            $this->getResponse()->setStatusCode(500);

            return new ViewModel(
                [
                    'success' => false,
                ],
            );
        }

        return new ViewModel(
            [
                'form' => $this->courseService->getTempUploadForm(),
            ],
        );
    }

    /**
     * Edit several exams in bulk.
     */
    public function editExamAction(): ViewModel
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $form = $this->courseService->getBulkExamForm();

        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                if ($this->courseService->bulkExamEdit($form->getData())) {
                    return new ViewModel(
                        [
                            'success' => true,
                        ],
                    );
                }
            }
        }

        return new ViewModel(
            [
                'form' => $form,
                'config' => $this->educationTempConfig,
            ],
        );
    }

    /**
     * Edit summaries in bulk.
     */
    public function editSummaryAction(): ViewModel
    {
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost() && $this->courseService->bulkSummaryEdit($request->getPost()->toArray())) {
            return new ViewModel(
                [
                    'success' => true,
                ],
            );
        }

        $config = $this->educationTempConfig;

        return new ViewModel(
            [
                'form' => $this->courseService->getBulkSummaryForm(),
                'config' => $config,
            ],
        );
    }

    public function deleteTempAction(): JsonModel|ViewModel
    {
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $this->courseService->deleteTempExam(
                $this->params()->fromRoute('filename'),
                $this->params()->fromRoute('type'),
            );

            return new JsonModel(['success' => 'true']);
        }

        return $this->notFoundAction();
    }
}
