<?php

namespace Photo\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class AlbumAdminController extends AbstractActionController
{

    /**
     * Retrieves the main photo admin index page.
     */
    public function indexAction()
    {
        $albumService = $this->getAlbumService();
        $years = $albumService->getAlbumYears();
        $albumsByYear = [];
        foreach ($years as $year) {
            $albumsByYear[$year] = $albumService->getAlbumsByYear($year);
        }

        $albumsWithoutDate = $albumService->getAlbumsWithoutDate();

        return new ViewModel([
            'albumsByYear' => $albumsByYear,
            'albumsWithoutDate' => $albumsWithoutDate
        ]);
    }

    /**
     * Retrieves the album creation form and saves data if needed.
     */
    public function createAction()
    {
        $albumService = $this->getAlbumService();
        $request = $this->getRequest();
        if ($request->isPost()) {
            $albumId = $this->params()->fromRoute('album_id');
            $album = $albumService->createAlbum($albumId, $request->getPost());
            if ($album) {
                $this->redirect()->toUrl($this->url()->fromRoute('admin_photo') . '#' . $album->getId());
            }
        }
        $form = $albumService->getCreateAlbumForm();

        return new ViewModel([
            'form' => $form,
        ]);
    }

    /**
     * Retrieves photos on a certain page
     */
    public function pageAction()
    {
        $albumId = $this->params()->fromRoute('album_id');
        $activePage = (int)$this->params()->fromRoute('page');
        $albumPage = $this->AlbumPlugin()->getAlbumPageAsArray($albumId, $activePage);
        if (is_null($albumPage)) {
            return $this->notFoundAction();
        }
        // Add some urls
        $albumPage['urls'] = $this->getURLs();

        return new JsonModel($albumPage);
    }

    /**
     * Retrieves the album editing form and saves changes.
     */
    public function editAction()
    {
        $albumService = $this->getAlbumService();
        $request = $this->getRequest();
        $albumId = $this->params()->fromRoute('album_id');
        if ($request->isPost()) {
            if ($albumService->updateAlbum($albumId, $request->getPost())) {
                return new ViewModel([
                    'success' => true
                ]);
            }
        }
        $form = $albumService->getEditAlbumForm($albumId);

        return new ViewModel([
            'form' => $form,
        ]);
    }

    public function addAction()
    {

    }

    /**
     * Uploads an image file and adds it to an album.
     */
    public function uploadAction()
    {
        $request = $this->getRequest();
        $result = [];
        $result['success'] = false;
        if ($request->isPost()) {
            $albumId = $this->params()->fromRoute('album_id');
            $album = $this->getAlbumService()->getAlbum($albumId);

            try {
                $this->getAdminService()->upload($request->getFiles(), $album);
                $result['success'] = true;
            } catch (\Exception $e) {
                $this->getResponse()->setStatusCode(500);
                $result['error'] = $e->getMessage();
            }
        }

        return new JsonModel($result);
    }

    /**
     * Imports photos from a given path in to an album.
     */
    public function importAction()
    {
        $request = $this->getRequest();
        $result = [];
        $result['success'] = false;
        if ($request->isPost()) {
            $albumId = $this->params()->fromRoute('album_id');
            $album = $this->getAlbumService()->getAlbum($albumId);
            try {
                $this->getAdminService()->storeUploadedDirectory($request->getPost()['folder_path'], $album);
                $result['success'] = true;
            } catch (\Exception $e) {
                $this->getResponse()->setStatusCode(500);
                $result['error'] = $e->getMessage();
            }
        }

        return new JsonModel($result);
    }

    /**
     * Moves the album by setting the parent album to another album.
     */
    public function moveAction()
    {
        $request = $this->getRequest();
        $result = [];
        if ($request->isPost()) {
            $albumId = $this->params()->fromRoute('album_id');
            $parentId = $request->getPost()['parent_id'];
            $result['success'] = $this->getAlbumService()->moveAlbum($albumId, $parentId);
        }

        return new JsonModel($result);
    }

    /**
     * Deletes the album.
     */
    public function deleteAction()
    {
        $request = $this->getRequest();
        $albumId = $this->params()->fromRoute('album_id');
        if ($request->isPost()) {
            $this->getAlbumService()->deleteAlbum($albumId);
        }

        return new JsonModel([]);
    }

    /**
     * Regenerates the cover photo for the album.
     */
    public function coverAction()
    {
        if ($this->getRequest()->isPost()) {
            $albumId = $this->params()->fromRoute('album_id');
            $this->getAlbumService()->generateAlbumCover($albumId);
        }

        return new JsonModel([]);
    }


    /**
     * Retrieves an associative array of URLs to be used by client side code.
     *
     * @return array
     */
    public function getURLs()
    {
        return [
            'album_edit' => $this->url()->fromRoute(
                'admin_photo/album_edit', ['album_id' => '{0}']
            ),
            'album_create' => $this->url()->fromRoute(
                'admin_photo/album_delete', ['album_id' => '{0}']
            ),
            'album_add' => $this->url()->fromRoute(
                'admin_photo/album_add', ['album_id' => '{0}']
            ),
            'album_move' => $this->url()->fromRoute(
                'admin_photo/album_move', ['album_id' => '{0}']
            ),
            'album_delete' => $this->url()->fromRoute(
                'admin_photo/album_delete', ['album_id' => '{0}']
            ),
            'album_cover' => $this->url()->fromRoute(
                'admin_photo/album_cover', ['album_id' => '{0}']
            ),
            'album_page' => $this->url()->fromRoute(
                'admin_photo/album_page', ['album_id' => '{0}', 'page' => '{1}']
            ),
            'album_index' => $this->url()->fromRoute(
                'admin_photo/album_index', ['album_id' => '{0}']
            ),
            'photo_index' => $this->url()->fromRoute(
                'admin_photo/photo_index', ['photo_id' => '{0}']
            ),
            'photo_delete' => $this->url()->fromRoute(
                'admin_photo/photo_delete', ['photo_id' => '{0}']
            ),
            'photo_move' => $this->url()->fromRoute(
                'admin_photo/photo_move', ['photo_id' => '{0}']
            ),


        ];
    }

    /**
     * Get the album service.
     *
     * @return \Photo\Service\Album
     */
    public function getAlbumService()
    {
        return $this->getServiceLocator()->get('photo_service_album');
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
