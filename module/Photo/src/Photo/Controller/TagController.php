<?php

namespace Photo\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class TagController extends AbstractActionController
{
    /**
     * @var \Photo\Service\Photo
     */
    private $photoService;

    function __construct(\Photo\Service\Photo $photoService)
    {
        $this->photoService = $photoService;
    }

    public function addAction()
    {
        $request = $this->getRequest();
        $result = [];
        if ($request->isPost()) {
            $photoId = $this->params()->fromRoute('photo_id');
            $lidnr = $this->params()->fromRoute('lidnr');
            $tag = $this->photoService->addTag($photoId, $lidnr);
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
            $result['success'] = $this->photoService->removeTag($photoId, $lidnr);
        }

        return new JsonModel($result);
    }
}
