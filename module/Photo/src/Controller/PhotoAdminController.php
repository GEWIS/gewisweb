<?php

declare(strict_types=1);

namespace Photo\Controller;

use Laminas\Http\Request;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Photo\Service\Album as AlbumService;
use Photo\Service\Photo as PhotoService;

use function intval;

class PhotoAdminController extends AbstractActionController
{
    public function __construct(
        private readonly AlbumService $albumService,
        private readonly PhotoService $photoService,
    ) {
    }

    /**
     * Places a photo in another album.
     */
    public function moveAction(): JsonModel
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $result = [];

        if ($request->isPost()) {
            $photoId = (int) $this->params()->fromRoute('photo_id');
            $albumId = intval($request->getPost()['album_id']);
            $result['success'] = $this->albumService->movePhoto($photoId, $albumId);
        }

        return new JsonModel($result);
    }

    /**
     * Removes a photo from an album and deletes it.
     */
    public function deleteAction(): JsonModel
    {
        /** @var Request $request */
        $request = $this->getRequest();

        $result = [];
        if ($request->isPost()) {
            $photoId = (int) $this->params()->fromRoute('photo_id');
            $result['success'] = $this->photoService->deletePhoto($photoId);
        }

        return new JsonModel($result);
    }
}
