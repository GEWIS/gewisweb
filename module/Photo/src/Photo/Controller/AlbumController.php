<?php

namespace Photo\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class AlbumController extends AbstractActionController
{

    public function indexAction()
    {
        $albumId = $this->params()->fromRoute('album_id');
        $activePage = (int) $this->params()->fromRoute('page');

        return new ViewModel($this->AlbumPlugin()->getAlbumPage($albumId, $activePage));
    }

}
