<?php

namespace Education\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class EducationController extends AbstractActionController {

    public function indexAction()
    {
        $service = $this->getExamService();
        $request = $this->getRequest();

        if ($request->isPost()) {
            $courses = $service->searchCourse($request->getPost());

            if (null !== $courses) {
                return new ViewModel(array(
                    'form' => $service->getSearchCourseForm(),
                    'courses' => $courses
                ));
            }
        }

        return new ViewModel(array(
            'form' => $service->getSearchCourseForm()
        ));
    }

    public function courseAction()
    {
        $code = $this->params()->fromRoute('code');
        $course = $this->getExamService()->getCourse($code);

        // when there is a parent course, redirect to that course
        if (null !== $course->getParent()) {
            return $this->redirect()->toRoute('education/course', array(
                'code' => $course->getParent()->getCode()
            ));
        }

        return new ViewModel(array(
            'course' => $course
        ));
    }

    /**
     * Get the exam service.
     */
    public function getExamService()
    {
        return $this->getServiceLocator()->get('education_service_exam');
    }
}
