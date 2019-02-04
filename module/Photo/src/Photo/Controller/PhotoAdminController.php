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
        $path = []; //The path to use in the breadcrumb navigation bar
        $parent = $data['photo']->getAlbum();
        while (!is_null($parent)) {
            $path[] = $parent;
            $parent = $parent->getParent();
        }

        return new ViewModel(array_merge($data, ['path' => $path]));
    }

    /**
     * Places a photo in another album.
     */
    public function moveAction()
    {
        $request = $this->getRequest();
        $result = [];
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
        $result = [];
        if ($request->isPost()) {
            $photoId = $this->params()->fromRoute('photo_id');
            $result['success'] = $this->getPhotoService()->deletePhoto($photoId);
        }

        return new JsonModel($result);
    }

    public function weeklyPhotoAction()
    {
        $weeklyPhoto = $this->getPhotoService()->generatePhotoOfTheWeek();

        if(is_null($weeklyPhoto)) {
            echo "No photo of the week chosen, were any photos viewed?\n";
        } else {
            echo "Photo of the week set to photo: " . $weeklyPhoto->getPhoto()->getId();
        }
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
