<?php

namespace Photo\Controller;

use Doctrine\ORM\EntityManager;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\{
    JsonModel,
    ViewModel,
};
use Photo\Service\{
    Album as AlbumService,
    Photo as PhotoService,
};

class PhotoAdminController extends AbstractActionController
{
    /**
     * @var AlbumService
     */
    private AlbumService $albumService;

    /**
     * @var PhotoService
     */
    private PhotoService $photoService;

    /**
     * PhotoAdminController constructor.
     *
     * @param AlbumService $albumService
     * @param PhotoService $photoService
     */
    public function __construct(
        AlbumService $albumService,
        PhotoService $photoService,
    ) {
        $this->albumService = $albumService;
        $this->photoService = $photoService;
    }

    /**
     * Shows an admin page for the specified photo.
     *
     * TODO: Potentially remove, as the admin interface can already move/delete images from the global view.
     */
    public function indexAction()
    {
        $photoId = $this->params()->fromRoute('photo_id');
        $data = $this->photoService->getPhotoData($photoId);

        if (is_null($data)) {
            return $this->notFoundAction();
        }

        $path = []; //The path to use in the breadcrumb navigation bar
        $parent = $data['photo']->getAlbum();
        while (!is_null($parent)) {
            $path[] = $parent;
            $parent = $parent->getParent();
        }

        return new ViewModel(array_merge($data, ['path' => $path]));
    }

    /**
     * Places a photo in another album.
     */
    public function moveAction()
    {
        $request = $this->getRequest();
        $result = [];
        if ($request->isPost()) {
            $photoId = $this->params()->fromRoute('photo_id');
            $albumId = $request->getPost()['album_id'];
            $result['success'] = $this->albumService->movePhoto($photoId, $albumId);
        }

        return new JsonModel($result);
    }

    /**
     * Removes a photo from an album and deletes it.
     */
    public function deleteAction()
    {
        $request = $this->getRequest();
        $result = [];
        if ($request->isPost()) {
            $photoId = $this->params()->fromRoute('photo_id');
            $result['success'] = $this->photoService->deletePhoto($photoId);
        }

        return new JsonModel($result);
    }
}
