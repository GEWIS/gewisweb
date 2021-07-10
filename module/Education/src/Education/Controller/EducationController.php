<?php

namespace Education\Controller;

use Education\Form\SearchCourse;
use Education\Service\Exam;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class EducationController extends AbstractActionController
{

    /**
     * @var Exam
     */
    private $examService;

    /**
     * @var SearchCourse
     */
    private $searchCourseForm;

    public function __construct(Exam $examService, SearchCourse $searchCourseForm)
    {
        $this->examService = $examService;
        $this->searchCourseForm = $searchCourseForm;
    }

    public function indexAction()
    {
        $request = $this->getRequest();

        $query = $request->getQuery();

        if (isset($query['query'])) {
            $courses = $this->examService->searchCourse($query);

            if (null !== $courses) {
                return new ViewModel([
                    'form' => $this->searchCourseForm,
                    'courses' => $courses
                ]);
            }
        }

        return new ViewModel([
            'form' => $this->searchCourseForm
        ]);
    }

    public function courseAction()
    {
        $code = $this->params()->fromRoute('code');
        $course = $this->examService->getCourse($code);

        // if the course did not exist, trigger 404
        if (is_null($course)) {
            return $this->notFoundAction();
        }

        // when there is a parent course, redirect to that course
        if (!is_null($course->getParent())) {
            return $this->redirect()->toRoute('education/course', [
                'code' => $course->getParent()->getCode()
            ]);
        }

        return new ViewModel([
            'course' => $course
        ]);
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
