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
      /*  $albumPage = $this->AlbumPlugin()->getAlbumPage($albumId, $activePage,
            'album');
        if (is_null($albumPage)) {
            return $this->notFoundAction();
        }*/
        $albumService = $this->getServiceLocator()
            ->get('photo_service_album');

        $album = $albumService->getAlbum($albumId, 'album');
        if ($album === null) {
            return $this->notFoundAction();
        }
        /*$albums = $albumService->getAlbums($this->album, $offset,
            $itemCountPerPage);

        $photoCount = $itemCountPerPage - count($albums);
        $photoStart = max($offset - $this->album->getAlbumCount(), 0);
        $photos = $photoService->getPhotos($this->album, $photoStart,
            $photoCount);

        $items = array_merge($albums, $photos);*/
        return new ViewModel([
            'cache' => $this->getServiceLocator()->get('album_page_cache'),
            'album'     => $album,
            'basedir'   => '/',
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
