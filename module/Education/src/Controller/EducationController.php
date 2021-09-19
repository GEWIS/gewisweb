<?php

namespace Education\Controller;

use Education\Form\SearchCourse as SearchCourseForm;
use Education\Service\Exam as ExamService;
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

    public function indexAction()
    {
        $request = $this->getRequest();

        $query = $request->getQuery();

        if (isset($query['query'])) {
            $courses = $this->examService->searchCourse($query->toArray());

            if (null !== $courses) {
                return new ViewModel(
                    [
                        'form' => $this->searchCourseForm,
                        'courses' => $courses,
                    ]
                );
            }
        }

        return new ViewModel(
            [
                'form' => $this->searchCourseForm,
            ]
        );
    }

    public function courseAction()
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
    public function downloadAction()
    {
        $id = $this->params()->fromRoute('id');

        return $this->examService->getExamDownload($id);
    }
}
