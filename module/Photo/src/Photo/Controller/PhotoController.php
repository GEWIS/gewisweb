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
        $basedir = $this->getPhotoService()->getBaseDirectory();

        return new ViewModel(array(
            'activeYear' => $year,
            'years' => $years,
            'albums' => $albums,
            'basedir' => $basedir
        ));
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

    public function downloadAction()
    {
        $photoId = $this->params()->fromRoute('photo_id');

        return $this->getPhotoService()->getPhotoDownload($photoId);
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
     * Gets the photo service.
     *
     * @return \Photo\Service\Photo
     */
    public function getPhotoService()
    {
        return $this->getServiceLocator()->get("photo_service_photo");
    }

}
