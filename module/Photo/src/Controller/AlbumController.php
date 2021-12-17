<?php

namespace Photo\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Photo\Service\Album as AlbumService;

class AlbumController extends AbstractActionController
{
    /**
     * @var AlbumService
     */
    private AlbumService $albumService;

    /**
     * @var array
     */
    private array $photoConfig;

    /**
     * AlbumController constructor.
     *
     * @param AlbumService $albumService
     * @param array $photoConfig
     */
    public function __construct(
        AlbumService $albumService,
        array $photoConfig,
    ) {
        $this->albumService = $albumService;
        $this->photoConfig = $photoConfig;
    }

    /**
     * Shows a page with all photos in an album, the album is either an actual
     * album or a member's album.
     *
     * @return ViewModel
     */
    public function indexAction()
    {
        $albumId = $this->params()->fromRoute('album_id');
        $albumType = $this->params()->fromRoute('album_type');

        $album = $this->albumService->getAlbum($albumId, $albumType);
        if (is_null($album)) {
            return $this->notFoundAction();
        }

        return new ViewModel(
            [
                'album' => $album,
                'basedir' => '/',
                'config' => $this->photoConfig,
            ]
        );
    }
}
