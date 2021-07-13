<?php

namespace Photo\Controller;

use Doctrine\ORM\EntityManager;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use Photo\Service\Album;
use Photo\Service\Photo;

class PhotoAdminController extends AbstractActionController
{
    /**
     * @var Photo
     */
    private $photoService;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Album
     */
    private $albumService;

    public function __construct(Photo $photoService, Album $albumService, EntityManager $entityManager)
    {
        $this->photoService = $photoService;
        $this->albumService = $albumService;
        $this->entityManager = $entityManager;
    }

    /**
     * Shows an admin page for the specified photo.
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

    public function weeklyPhotoAction()
    {
        $weeklyPhoto = $this->photoService->generatePhotoOfTheWeek();

        if (is_null($weeklyPhoto)) {
            echo "No photo of the week chosen, were any photos viewed?\n";
        } else {
            echo 'Photo of the week set to photo: '.$weeklyPhoto->getPhoto()->getId();
        }
    }

    /**
     * Temp function to migrate to new storage format.
     */
    public function migrateAspectRatiosAction()
    {
        printf("Migrating aspect ratios\n");
        $em = $this->entityManager;
        $qb = $em->createQueryBuilder();

        $qb->select('a')
            ->from('Photo\Model\Photo', 'a')
            ->where('a.aspectRatio is NULL')
            ->orderBy('a.dateTime', 'DESC');

        $photos = $qb->getQuery()->getResult();
        $i = 0;
        foreach ($photos as $photo) {
            ++$i;
            $ratio = 0;
            if (!file_exists('public/data/'.$photo->getSmallThumbPath())) {
                printf("Missing file: %s\n", $photo->getSmallThumbPath());
                continue;
            }
            $size = getimagesize('public/data/'.$photo->getSmallThumbPath());
            if ($size[0] > 0) {
                $ratio = $size[1] / $size[0];
            }
            $photo->setAspectRatio($ratio);
            if (0 == $i % 1000) {
                $em->flush();
            }
        }
        $em->flush();
    }
}
