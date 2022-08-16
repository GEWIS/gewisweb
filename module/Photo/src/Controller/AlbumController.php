<?php

namespace Photo\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Photo\Service\{
    Album as AlbumService,
    Photo as PhotoService,
};

class AlbumController extends AbstractActionController
{
    public function __construct(
        private readonly AlbumService $albumService,
        private readonly PhotoService $photoService,
        private readonly array $photoConfig,
    ) {
    }

    /**
     * Shows a page with all photos in an album, the album is either an actual
     * album or a member's album.
     *
     * @return ViewModel
     */
    public function indexAction(): ViewModel
    {
        $albumId = $this->params()->fromRoute('album_id');
        $albumType = $this->params()->fromRoute('album_type');

        $album = $this->albumService->getAlbum($albumId, $albumType);
        if (is_null($album)) {
            return $this->notFoundAction();
        }

        $hasRecentVote = $this->photoService->hasRecentVote();

        return new ViewModel(
            [
                'album' => $album,
                'basedir' => '/',
                'config' => $this->photoConfig,
                'hasRecentVote' => $hasRecentVote,
            ]
        );
    }
}
