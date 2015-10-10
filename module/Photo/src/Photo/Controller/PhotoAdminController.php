<?php

namespace Photo\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class PhotoAdminController extends AbstractActionController
{

    /**
     * Shows an admin page for the specified photo
     */
    public function indexAction()
    {
        $photoId = $this->params()->fromRoute('photo_id');
        $data = $this->getPhotoService()->getPhotoData($photoId);
        if (is_null($data)) {
            return $this->notFoundAction();
        }
        $path = array(); //The path to use in the breadcrumb navigation bar
        $parent = $data['photo']->getAlbum();
        while (!is_null($parent)) {
            $path[] = $parent;
            $parent = $parent->getParent();
        }

        return new ViewModel(array_merge($data, array('path' => $path)));
    }

    /**
     * Places a photo in another album.
     */
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

    /**
     * Removes a photo from an album and deletes it.
     */
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

    public function weeklyPhotoAction()
    {
        $this->getPhotoService()->generatePhotoOfTheWeek(
            (new \DateTime())->sub(new \DateInterval('P2Y')),
            new \DateTime()
        );
    }
    /**
     * Get the photo service.
     *
     * @return \Photo\Service\Photo
     */
    public function getPhotoService()
    {
        return $this->getServiceLocator()->get('photo_service_photo');
    }

    /**
     * Get the photo admin service.
     *
     * @return \Photo\Service\Admin
     */
    public function getAdminService()
    {
        return $this->getServiceLocator()->get("photo_service_admin");
    }

}
