<?php

namespace Photo\Controller;

use Exception;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\{
    JsonModel,
    ViewModel,
};
use Photo\Service\{
    Album as AlbumService,
    Photo as PhotoService,
};

class PhotoController extends AbstractActionController
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
     * PhotoController constructor.
     *
     * @param AlbumService $albumService
     * @param PhotoService $photoService
     */
    public function __construct(
        AlbumService $albumService,
        PhotoService $photoService
    ) {
        $this->photoService = $photoService;
        $this->albumService = $albumService;
    }

    public function indexAction()
    {
        //add any other special behavior which is required for the main photo page here later
        $years = $this->albumService->getAlbumYears();
        $year = $this->params()->fromRoute('year');
        // If no year is supplied, use the latest year.
        if (is_null($year)) {
            $year = max($years);
        } else {
            $year = (int)$year;
        }

        $albums = $this->albumService->getAlbumsByYear($year);

        return new ViewModel(
            [
                'activeYear' => $year,
                'years' => $years,
                'albums' => $albums,
            ]
        );
    }

    public function downloadAction()
    {
        $photoId = $this->params()->fromRoute('photo_id');

        return $this->photoService->getPhotoDownload($photoId);
    }

    /**
     * Display the page containing previous pictures of the week.
     */
    public function weeklyAction()
    {
        $weeklyPhotos = $this->photoService->getPhotosOfTheWeek();

        return new ViewModel(
            [
                'weeklyPhotos' => $weeklyPhotos,
            ]
        );
    }

    /**
     * For setting a profile picture.
     */
    public function setProfilePhotoAction()
    {
        $photoId = $this->params()->fromRoute('photo_id');
        $this->photoService->setProfilePhoto($photoId);

        return $this->redirect()->toRoute(
            'photo/photo',
            [
                'photo_id' => $photoId,
            ]
        );
    }

    /**
     * For removing a profile picture.
     */
    public function removeProfilePhotoAction()
    {
        $photoId = $this->params()->fromRoute('photo_id', null);
        $this->photoService->removeProfilePhoto();

        if (null != $photoId) {
            return $this->redirect()->toRoute(
                'photo/photo',
                [
                    'photo_id' => $photoId,
                ]
            );
        }

        return $this->redirect()->toRoute('member/self');
    }

    /**
     * Store a vote for a photo.
     */
    public function voteAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $photoId = $this->params()->fromRoute('photo_id');
            $this->photoService->countVote($photoId);

            return new JsonModel(['success' => true]);
        }

        return $this->getResponse();
    }
}
