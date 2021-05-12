<?php

namespace Photo\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class ApiController extends AbstractActionController
{

    /**
     * Retrieve a list of all photo's in an album.
     *
     * This API call is intended for external scripts. Like the AViCo TV screen
     * that needs a list of all photo's.
     */
    public function listAction()
    {
        $albumId = $this->params()->fromRoute('album_id');
        $album = $this->AlbumPlugin()->getAlbumAsArray($albumId);
        if (is_null($albumId)) {
            return $this->notFoundAction();
        }

        return new JsonModel($album);
    }
}
