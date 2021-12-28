<?php

namespace Education\Controller;

use Education\Form\SearchCourse as SearchCourseForm;
use Education\Service\Exam as ExamService;
use Laminas\Http\Response;
use Laminas\Http\Response\Stream;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class EducationController extends AbstractActionController
{
    /**
     * @var ExamService
     */
    private ExamService $examService;

    /**
     * @var SearchCourseForm
     */
    private SearchCourseForm $searchCourseForm;

    /**
     * EducationController constructor.
     *
     * @param ExamService $examService
     * @param SearchCourseForm $searchCourseForm
     */
    public function __construct(
        ExamService $examService,
        SearchCourseForm $searchCourseForm
    ) {
        $this->examService = $examService;
        $this->searchCourseForm = $searchCourseForm;
    }

    public function indexAction(): ViewModel
    {
        $query = $this->getRequest()->getQuery();
        $form = $this->searchCourseForm;

        if (isset($query['query'])) {
            $form->setData($query->toArray());

            if ($form->isValid()) {
                $courses = $this->examService->searchCourse($form->getData());

                if (null !== $courses) {
                    return new ViewModel(
                        [
                            'form' => $form,
                            'courses' => $courses,
                        ]
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

    public function courseAction(): Response|ViewModel
    {
        $code = $this->params()->fromRoute('code');
        $course = $this->examService->getCourse($code);

        // if the course did not exist, trigger 404
        if (null === $course) {
            return $this->notFoundAction();
        }

        // when there is a parent course, redirect to that course
        if (null !== $course->getParent()) {
            return $this->redirect()->toRoute(
                'education/course',
                [
                    'code' => $course->getParent()->getCode(),
                ]
            );
        }

        return new ViewModel(
            [
                'course' => $course,
            ]
        );
    }

    /**
     * Download an exam.
     */
    public function downloadAction(): Stream|ViewModel
    {
        $id = $this->params()->fromRoute('id');

        $download = $this->examService->getExamDownload($id);

        if (is_null($download)) {
            return $this->notFoundAction();
        }

        return $download;
    }
}
