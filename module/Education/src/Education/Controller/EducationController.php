<?php

namespace Education\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class EducationController extends AbstractActionController {

    public function indexAction()
    {
        $service = $this->getExamService();
        $request = $this->getRequest();

        $query = $request->getQuery();

        if (isset($query['query'])) {
            $courses = $service->searchCourse($query);

            if (null !== $courses) {
                return new ViewModel([
                    'form' => $service->getSearchCourseForm(),
                    'courses' => $courses
                ]);
            }
        }

        return new ViewModel([
            'form' => $service->getSearchCourseForm()
        ]);
    }

    public function courseAction()
    {
        $code = $this->params()->fromRoute('code');
        $course = $this->getExamService()->getCourse($code);

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

        return $this->getExamService()->getExamDownload($id);
    }

    /**
     * Get the exam service.
     */
    public function getExamService()
    {
        return $this->getServiceLocator()->get('education_service_exam');
    }
}
