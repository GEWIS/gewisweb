<?php

namespace Photo\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class TagController extends AbstractActionController
{
    public function addAction()
    {
        $request = $this->getRequest();
        $result = [];
        if ($request->isPost()) {
            $photoId = $this->params()->fromRoute('photo_id');
            $lidnr = $this->params()->fromRoute('lidnr');
            $tag = $this->getPhotoService()->addTag($photoId, $lidnr);
            if (is_null($tag)) {
                $result['success'] = false;
            } else {
                $result['success'] = true;
                $result['tag'] = $tag->toArray();
            }
        }

        return new JsonModel($result);
    }

    public function removeAction()
    {
        $request = $this->getRequest();
        $result = [];
        if ($request->isPost()) {
            $photoId = $this->params()->fromRoute('photo_id');
            $lidnr = $this->params()->fromRoute('lidnr');
            $result['success'] = $this->getPhotoService()->removeTag($photoId, $lidnr);
        }

        return new JsonModel($result);
    }

    /**
     * Gets the photo service.
     *
     * @return \Photo\Service\Photo
     */
    public function getPhotoService()
    {
        return $this->getServiceLocator()->get("photo_service_photo");
    }
}