<?php

namespace Photo\Controller;

use Laminas\Http\Response;
use Laminas\Http\Response\Stream;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\{
    JsonModel,
    ViewModel,
};
use Photo\Service\{
    AclService,
    Album as AlbumService,
    Photo as PhotoService,
};
use Laminas\Mvc\I18n\Translator;
use User\Permissions\NotAllowedException;

class PhotoController extends AbstractActionController
{
    private Translator $translator;

    private AclService $aclService;

    /**
     * @var AlbumService
     */
    private AlbumService $albumService;

    /**
     * @var PhotoService
     */
    private PhotoService $photoService;

    private array $photoConfig;

    /**
     * PhotoController constructor.
     *
     * @param Translator $translator
     * @param AclService $aclService
     * @param AlbumService $albumService
     * @param PhotoService $photoService
     * @param array $photoConfig
     */
    public function __construct(
        Translator $translator,
        AclService $aclService,
        AlbumService $albumService,
        PhotoService $photoService,
        array $photoConfig,
    ) {
        $this->translator = $translator;
        $this->aclService = $aclService;
        $this->photoService = $photoService;
        $this->albumService = $albumService;
        $this->photoConfig = $photoConfig;
    }

    public function indexAction(): ViewModel
    {
        $years = $this->albumService->getAlbumYears();
        $year = $this->params()->fromRoute('year');

        // If no year is supplied, use the latest year.
        if (null === $year) {
            if (0 === count($years)) {
                $year = (int) date('Y');
            } else {
                $year = max($years);
            }
        } else {
            $year = (int) $year;
        }

        return new ViewModel(
            [
                'years' => $years,
                'albums' => $this->albumService->getAlbumsByYear($year),
            ]
        );
    }

    public function downloadAction(): ?Stream
    {
        $photoId = $this->params()->fromRoute('photo_id');

        return $this->photoService->getPhotoDownload($photoId);
    }

    /**
     * Display the page containing previous pictures of the week.
     */
    public function weeklyAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('view', 'photo')) {
            throw new NotAllowedException(
                $this->translator->translate('Not allowed to view previous photos of the week')
            );
        }

        return new ViewModel(
            [
                'config' => $this->photoConfig,
                'photosOfTheWeek' => $this->albumService->getLastPhotosOfTheWeekPerYear(),
            ]
        );
    }

    /**
     * For setting a profile picture.
     */
    public function setProfilePhotoAction(): JsonModel|ViewModel
    {
        if ($this->getRequest()->isPost()) {
            $photoId = $this->params()->fromRoute('photo_id');
            $this->photoService->setProfilePhoto($photoId);

            return new JsonModel(['success' => true]);
        }

        return $this->notFoundAction();
    }

    /**
     * For removing a profile picture.
     */
    public function removeProfilePhotoAction(): Response
    {
        $photoId = $this->params()->fromRoute('photo_id');
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
    public function voteAction(): JsonModel|ViewModel
    {
        if ($this->getRequest()->isPost()) {
            $photoId = $this->params()->fromRoute('photo_id');
            $this->photoService->countVote($photoId);

            return new JsonModel(['success' => true]);
        }

        return $this->notFoundAction();
    }
}
