<?php

namespace Photo\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class PhotoAdminController extends AbstractActionController
{

    public function indexAction()
    {
        $photoId = $this->params()->fromRoute('photo_id');
        $data = $this->getPhotoService()->getPhotoData($photoId);
        $path = array(); //The path to use in the breadcrumb navigation bar
        $parent = $data['photo']->getAlbum();
        while(!is_null($parent)) {
            $path[] = $parent;
            $parent = $parent->getParent();
        }
        return new ViewModel(array_merge($data, array('path' => $path)));
    }

    public function moveAction()
    {
        $request = $this->getRequest();
        $result = array();
        if ($request->isPost()) {
            $photoId = $this->params()->fromRoute('photo_id');
            $albumId = $request->getPost()['album_id'];
            $result['success'] = $this->getPhotoService()->movePhoto($photoId, $albumId);
        }
        return new JsonModel($result);
    }

    public function deleteAction()
    {
        $request = $this->getRequest();
        $result = array();
        if ($request->isPost()) {
            $photoId = $this->params()->fromRoute('photo_id');
            $result['success'] = $this->getPhotoService()->deletePhoto($photoId);
        }
        return new JsonModel($result);
    }

    /**
     * Get the photo service.
     */
    public function getPhotoService()
    {
        return $this->getServiceLocator()->get('photo_service_photo');
    }

}
