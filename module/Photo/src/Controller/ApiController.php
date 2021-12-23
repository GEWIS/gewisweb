<?php

namespace Photo\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;

class ApiController extends AbstractActionController
{
    /**
     * Retrieve a list of all photo's in an album.
     *
     * This API call is intended for external scripts. Like the AViCo TV screen
     * that needs a list of all photo's.
     */
    public function listAction(): JsonModel|ViewModel
    {
        $albumId = $this->params()->fromRoute('album_id');
        $album = $this->plugin('AlbumPlugin')->getAlbumAsArray($albumId);
        if (is_null($albumId)) {
            return $this->notFoundAction();
        }

        return new JsonModel($album);
    }
}
