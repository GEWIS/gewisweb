<?php

namespace Photo\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class PhotoController extends AbstractActionController
{

    public function indexAction()
    {
        //add any other special behavior which is required for the main photo page here later
        $years = $this->getAlbumService()->getAlbumYears();
        $year = $this->params()->fromRoute('year');
        // If no year is supplied, use the latest year.
        if (is_null($year)) {
            $year = max($years);
        } else {
            $year = (int)$year;
        }
        $albums = $this->getAlbumService()->getAlbumsByYear($year);

        return new ViewModel([
            'activeYear' => $year,
            'years'      => $years,
            'albums'     => $albums
        ]);
    }

    /**
     * Gets the album service.
     *
     * @return \Photo\Service\Album
     */
    public function getAlbumService()
    {
        return $this->getServiceLocator()->get("photo_service_album");
    }

    /**
     * Called on viewing a photo
     *
     */
    public function viewAction()
    {
        $photoId = $this->params()->fromRoute('photo_id');
        $photoData = $this->getPhotoService()->getPhotoData($photoId);

        if (is_null($photoData)) {
            return $this->notFoundAction();
        }

        $this->getPhotoService()->countHit($photoData['photo']);

        return new ViewModel($photoData);
    }

    /**
     * Gets the photo service.
     *
     * @return \Photo\Service\Photo
     */
    public function getPhotoService()
    {
        return $this->getServiceLocator()->get("photo_service_photo");
    }

    /**
     * Called on viewing a photo in an album for a member
     *
     * @return ViewModel
     */
    public function memberAction()
    {
        $lidnr = $this->params()->fromRoute('lidnr');
        $page = $this->params()->fromRoute('page');
        $photoId = $this->params()->fromRoute('photo_id');
        try {
            $memberAlbum = $this->getAlbumService()->getAlbum($lidnr, 'member');
        } catch (\Exception $e) {
            return $this->notFoundAction();
        }
        $photoData = $this->getPhotoService()->getPhotoData($photoId,
            $memberAlbum);

        if (is_null($photoData)) {
            return $this->notFoundAction();
        }

        $photoData = array_merge($photoData, [
            'memberAlbum'     => $memberAlbum,
            'memberAlbumPage' => $page,
        ]);

        $this->getPhotoService()->countHit($photoData['photo']);

        $vm = new ViewModel($photoData);
        $vm->setTemplate('photo/view');

        return $vm;
    }

    public function downloadAction()
    {
        $photoId = $this->params()->fromRoute('photo_id');
        $options = [
            'w' => $this->params()->fromQuery('w'),
            'h' => $this->params()->fromQuery('h')
        ];
        return $this->getPhotoService()->getPhotoDownload($photoId, $options);
    }

    /**
     * Display the page containing previous pictures of the week.
     */
    public function weeklyAction()
    {
        $weeklyPhotos = $this->getPhotoService()->getPhotosOfTheWeek();

        return new ViewModel([
            'weeklyPhotos' => $weeklyPhotos
        ]);
    }

    /**
     * For setting a profile picture
     */
    public function setProfilePhotoAction()
    {
        $photoId = $this->params()->fromRoute('photo_id');
        $this->getPhotoService()->setProfilePhoto($photoId);

        $this->redirect()->toRoute('photo/photo', [
            'photo_id' => $photoId,
        ]);
    }

    /**
     * For removing a profile picture
     */
    public function removeProfilePhotoAction()
    {
        $photoId = $this->params()->fromRoute('photo_id', null);
        $this->getPhotoService()->removeProfilePhoto();

        if ($photoId != null) {
            $this->redirect()->toRoute('photo/photo', [
                'photo_id' => $photoId,
            ]);
        } else {
            $this->redirect()->toRoute('member/self');
        }
    }
    
}
