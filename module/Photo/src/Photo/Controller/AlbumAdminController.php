<?php

namespace Photo\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class AlbumAdminController extends AbstractActionController
{

    public function indexAction()
    {
        $albumService = $this->getAlbumService();
        $albums = $albumService->getAlbums();
        return new ViewModel(array(
            'albums' => $albums
        ));
    }

    public function createAction()
    {
        $service = $this->getAlbumService();
        $request = $this->getRequest();

        if ($request->isPost()) {
            //TODO: save and create album
        }

        return new ViewModel(array(
            'form' => $service->getCreateAlbumForm()
        ));
    }

    public function pageAction()
    {
        $albumId = $this->params()->fromRoute('album_id');
        $activePage = (int) $this->params()->fromRoute('page');
        $data = $this->AlbumPlugin()->getAlbumPage($albumId, $activePage);

        //TODO: Fix these ugly hacks below this line!!
        $data['album'] = (array) $data['album'];
        foreach ($data['album'] as $key => $value) {
                $data['album'][str_replace("\0*\0","", $key)] = $data['album'][$key];
                unset($data['album'][$key]);
            }
        for ($i = 0; $i < count($data['albums']); $i++) {
            $data['albums'][$i] = (array) $data['albums'][$i];
            foreach ($data['albums'][$i] as $key => $value) {
                $data['albums'][$i][str_replace("\0*\0","", $key)] = $data['albums'][$i][$key];
                unset($data['albums'][$i][$key]);
            }
        }
        for ($i = 0; $i < count($data['photos']); $i++) {
            $data['photos'][$i] = (array) $data['photos'][$i];
            foreach ($data['photos'][$i] as $key => $value) {
                $data['photos'][$i][str_replace("\0*\0","", $key)] = $data['photos'][$i][$key];
                unset($data['photos'][$i][$key]);
            }
        }
        return new JsonModel($data);
    }

    public function editAction()
    {
        
    }

    public function addAction()
    {
        
    }

    public function uploadAction()
    {
        
    }

    public function importAction()
    {
        $form = $this->getAlbumService()->getPhotoImportForm();

        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($request->getPost());
            if ($form->isValid()) {
                $album = $this->getAlbumService()->getAlbum($request->getPost()['album_id']);
                $this->getPhotoService()->storeUploadedDirectory($request->getPost()['folder_path'], $album);
            }
        }

        return new ViewModel(array(
            'form' => $form
        ));
    }

    public function moveAction()
    {
        
    }

    public function deleteAction()
    {
        
    }

    /**
     * Get the album service.
     */
    public function getAlbumService()
    {
        return $this->getServiceLocator()->get('photo_service_album');
    }

    /**
     * Get the photo service.
     */
    public function getPhotoService()
    {
        return $this->getServiceLocator()->get('photo_service_photo');
    }

}
