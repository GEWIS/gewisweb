<?php

namespace Photo\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class AlbumController extends AbstractActionController
{
    /**
     * Shows a page from the album, or a 404 if this page does not exist
     *
     * @return array|ViewModel
     */
    public function indexAction()
    {
        $albumId = $this->params()->fromRoute('album_id');
        $activePage = (int)$this->params()->fromRoute('page');
        $albumPage = $this->AlbumPlugin()->getAlbumPage($albumId, $activePage,
            'album');
        if (is_null($albumPage)) {
            return $this->notFoundAction();
        }

        return new ViewModel($albumPage);
    }

    /**
     * Shows a page with all photos in an album, the album is either an actual
     * album or a member's album.
     *
     * @return ViewModel
     */
    public function indexNewAction()
    {
        $albumId = $this->params()->fromRoute('album_id');
        $albumType = $this->params()->fromRoute('album_type');
        $albumService = $this->getServiceLocator()->get('photo_service_album');

        $album = $albumService->getAlbum($albumId, $albumType);
        if (is_null($album)) {
            return $this->notFoundAction();
        }

        return new ViewModel([
            'cache' => $this->getServiceLocator()->get('album_page_cache'),
            'album' => $album,
            'basedir' => '/',
            'config' => $this->getServiceLocator()->get('config')['photo'],
        ]);
    }

    /**
     * Shows a page with photo's of a member, or a 404 if this page does not
     * exist
     *
     * @return array|ViewModel
     */
    public function memberAction()
    {
        $lidnr = (int)$this->params()->fromRoute('lidnr');
        $activePage = (int)$this->params()->fromRoute('page');
        $albumPage = $this->AlbumPlugin()->getAlbumPage($lidnr, $activePage,
            'member');
        
        if (is_null($albumPage)) {
            return $this->notFoundAction();
        }
        
        $vm = new ViewModel($albumPage);
        $vm->setTemplate('photo/album/index');
        
        return $vm;
    }
}
