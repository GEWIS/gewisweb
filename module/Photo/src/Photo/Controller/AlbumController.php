<?php

namespace Photo\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class AlbumController extends AbstractActionController
{
    /**
     * Shows an page from the album, or a 404 if this page does not exist
     * @return array|ViewModel
     */
    public function indexAction()
    {
        $albumId = $this->params()->fromRoute('album_id');
        $activePage = (int) $this->params()->fromRoute('page');
        $albumPage = $this->AlbumPlugin()->getAlbumPage($albumId, $activePage);
        if (is_null($albumPage)) {
            return $this->notFoundAction();
        }
        return new ViewModel($albumPage);
    }

}
