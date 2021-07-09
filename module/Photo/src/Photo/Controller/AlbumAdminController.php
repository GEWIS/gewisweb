<?php

namespace Photo\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class AlbumAdminController extends AbstractActionController
{
    /**
     * @var \Photo\Service\Admin
     */
    private $adminService;

    /**
     * @var \Photo\Service\Album
     */
    private $albumService;

    public function __construct(\Photo\Service\Admin $adminService, \Photo\Service\Album $albumService)
    {
        $this->adminService = $adminService;
        $this->albumService = $albumService;
    }

    /**
     * Retrieves the main photo admin index page.
     */
    public function indexAction()
    {
        $years = $this->albumService->getAlbumYears();
        $albumsByYear = [];
        foreach ($years as $year) {
            $albumsByYear[$year] = $this->albumService->getAlbumsByYear($year);
        }

        $albumsWithoutDate = $this->albumService->getAlbumsWithoutDate();

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
        $request = $this->getRequest();
        if ($request->isPost()) {
            $albumId = $this->params()->fromRoute('album_id');
            $album = $this->albumService->createAlbum($albumId, $request->getPost());
            if ($album) {
                $this->redirect()->toUrl($this->url()->fromRoute('admin_photo') . '#' . $album->getId());
            }
        }
        $form = $this->albumService->getCreateAlbumForm();

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
        $activePage = (int) $this->params()->fromRoute('page');
        $albumPage = $this->AlbumPlugin()->getAlbumPageAsArray($albumId, $activePage);
        if (is_null($albumPage)) {
            return $this->notFoundAction();
        }

        return new JsonModel($albumPage);
    }

    /**
     * Retrieves the album editing form and saves changes.
     */
    public function editAction()
    {
        $request = $this->getRequest();
        $albumId = $this->params()->fromRoute('album_id');
        if ($request->isPost()) {
            if ($this->albumService->updateAlbum($albumId, $request->getPost())) {
                $this->redirect()->toUrl($this->url()->fromRoute('admin_photo') . '#' . $albumId);
            }
        }
        $form = $this->albumService->getEditAlbumForm($albumId);

        return new ViewModel([
            'form' => $form,
        ]);
    }

    public function addAction()
    {
        $this->adminService->checkUploadAllowed();

        $albumId = $this->params()->fromRoute('album_id');
        $album = $this->albumService->getAlbum($albumId);

        return new ViewModel([
            'album' => $album
        ]);
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
            $album = $this->albumService->getAlbum($albumId);

            try {
                $this->adminService->upload($request->getFiles(), $album);
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
            $album = $this->albumService->getAlbum($albumId);
            try {
                $this->adminService->storeUploadedDirectory($request->getPost()['folder_path'], $album);
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
            $result['success'] = $this->albumService->moveAlbum($albumId, $parentId);
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
            $this->albumService->deleteAlbum($albumId);
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
            $this->albumService->generateAlbumCover($albumId);
        }

        return new JsonModel([]);
    }
}
