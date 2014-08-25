<?php

namespace Photo\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class AdminController extends AbstractActionController {

    public function indexAction() {
        
    }

    public function uploadAction() {
        
    }

    public function viewAlbumAction() {
        
    }

    public function createAlbumAction() {
        
    }

    public function albumAction() {
        $service = $this->getAlbumService();
        $albums = $service->getAlbumTree();
        return new ViewModel(array(
            'albums' => $albums
        ));
    }

    public function albumCreateAction() {
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

    /**
     * Get the album service.
     */
    public function getAlbumService() {
        return $this->getServiceLocator()->get('photo_service_album');
    }

}
