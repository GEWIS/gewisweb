<?php

namespace Photo\Controller;

use Photo\Service\Album;
use Zend\Cache\Storage\StorageInterface;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class AlbumController extends AbstractActionController
{
    /**
     * @var Album
     */
    private $albumService;

    /**
     * @var Storage
     */
    private $pageCache;

    /**
     * @var array
     */
    private $photoConfig;

    public function __construct(Album $albumService, StorageInterface $pageCache, array $photoConfig)
    {
        $this->albumService = $albumService;
        $this->pageCache = $pageCache;
        $this->photoConfig = $photoConfig;
    }

    /**
     * Shows a page from the album, or a 404 if this page does not exist
     *
     * @return array|ViewModel
     */
    public function indexAction()
    {
        $albumId = $this->params()->fromRoute('album_id');
        $activePage = (int) $this->params()->fromRoute('page');
        $albumPage = $this->AlbumPlugin()->getAlbumPage($albumId, $activePage,
            'album');
        if (is_null($albumPage)) {
            return $this->notFoundAction();
        }

        return new ViewModel($albumPage);
    }

    /**
     * Shows a page with all photos in an album, the album is either an actual
     * album or a member's album.
     *
     * @return ViewModel
     */
    public function indexNewAction()
    {
        $albumId = $this->params()->fromRoute('album_id');
        $albumType = $this->params()->fromRoute('album_type');

        $album = $this->albumService->getAlbum($albumId, $albumType);
        if (is_null($album)) {
            return $this->notFoundAction();
        }

        return new ViewModel([
            'cache' => $this->pageCache,
            'album' => $album,
            'basedir' => '/',
            'config' => $this->photoConfig,
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
        $lidnr = (int) $this->params()->fromRoute('lidnr');
        $activePage = (int) $this->params()->fromRoute('page');
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
