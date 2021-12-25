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
    /**
     * @var AlbumService
     */
    private AlbumService $albumService;

    /**
     * @var PhotoService
     */
    private PhotoService $photoService;

    /**
     * @var array
     */
    private array $photoConfig;

    /**
     * AlbumController constructor.
     *
     * @param AlbumService $albumService
     * @param PhotoService $photoService
     * @param array $photoConfig
     */
    public function __construct(
        AlbumService $albumService,
        PhotoService $photoService,
        array $photoConfig,
    ) {
        $this->albumService = $albumService;
        $this->photoService = $photoService;
        $this->photoConfig = $photoConfig;
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
