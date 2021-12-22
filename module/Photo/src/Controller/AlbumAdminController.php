<?php

namespace Photo\Controller;

use Exception;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\{
    JsonModel,
    ViewModel,
};
use Photo\Service\{
    Admin as AdminService,
    Album as AlbumService,
};

class AlbumAdminController extends AbstractActionController
{
    /**
     * @var AdminService
     */
    private AdminService $adminService;

    /**
     * @var AlbumService
     */
    private AlbumService $albumService;

    /**
     * AlbumAdminController constructor.
     *
     * @param AdminService $adminService
     * @param AlbumService $albumService
     */
    public function __construct(
        AdminService $adminService,
        AlbumService $albumService,
    ) {
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

        return new ViewModel(
            [
                'albumsByYear' => $albumsByYear,
                'albumsWithoutDate' => $albumsWithoutDate,
            ]
        );
    }

    /**
     * Retrieves the album creation form and saves data if needed.
     */
    public function createAction()
    {
        $form = $this->albumService->getCreateAlbumForm();

        $request = $this->getRequest();
        if ($request->isPost()) {
            $albumId = $this->params()->fromRoute('album_id');
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                if (null !== ($album = $this->albumService->createAlbum($albumId, $form->getData()))) {
                    return $this->redirect()->toUrl($this->url()->fromRoute('admin_photo') . '#' . $album->getId());
                }
            }
        }

        return new ViewModel(
            [
                'form' => $form,
            ]
        );
    }

    /**
     * Retrieves photos on a certain page.
     */
    public function pageAction()
    {
        $albumId = $this->params()->fromRoute('album_id');
        $activePage = (int) $this->params()->fromRoute('page');

        if (null !== $albumId) {
            $albumPage = $this->plugin('AlbumPlugin')->getAlbumPageAsArray($albumId, $activePage);

            if (null !== $albumPage) {
                return new JsonModel($albumPage);
            }
        }

        return $this->notFoundAction();
    }

    /**
     * Retrieves the album editing form and saves changes.
     */
    public function editAction()
    {
        $albumId = $this->params()->fromRoute('album_id');
        $form = $this->albumService->getEditAlbumForm($albumId);

        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                if ($this->albumService->updateAlbum()) {
                    return $this->redirect()->toUrl($this->url()->fromRoute('admin_photo') . '#' . $albumId);
                }
            }
        }

        return new ViewModel(
            [
                'form' => $form,
            ]
        );
    }

    public function addAction()
    {
        $this->adminService->checkUploadAllowed();

        $albumId = $this->params()->fromRoute('album_id');
        $album = $this->albumService->getAlbum($albumId);

        return new ViewModel(
            [
                'album' => $album,
            ]
        );
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
                $this->adminService->upload($request->getFiles()->toArray(), $album);
                $result['success'] = true;
            } catch (Exception $e) {
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
            $parentId = (int) $request->getPost()['parent_id'];

            if (0 === $parentId) {
                $parentId = null;
            }

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
