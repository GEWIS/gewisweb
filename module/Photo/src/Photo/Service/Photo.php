<?php

namespace Photo\Service;

use Application\Service\AbstractAclService;
use Photo\Model\Hit as HitModel;
use Photo\Model\Tag as TagModel;
use Photo\Model\WeeklyPhoto as WeeklyPhotoModel;

/**
 * Photo service.
 */
class Photo extends AbstractAclService
{
    /**
     * Retrieves a photo by an id.
     *
     * @param integer $id the id of the album
     * @return \Photo\Model\Photo photo matching the given id
     */
    public function getPhoto($id)
    {
        if (!$this->isAllowed('view')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('Not allowed to view photos')
            );
        }

        return $this->getPhotoMapper()->getPhotoById($id);
    }

    /**
     * Returns the next photo in the album to display
     *
     * @param \Photo\Model\Photo $photo
     *
     * @return \Photo\Model\Photo The next photo.
     */
    public function getNextPhoto($photo)
    {
        if (!$this->isAllowed('view')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('Not allowed to view photos')
            );
        }

        return $this->getPhotoMapper()->getNextPhoto($photo);
    }

    /**
     * Returns the previous photo in the album to display
     *
     * @param \Photo\Model\Photo $photo
     *
     * @return \Photo\Model\Photo The next photo.
     */
    public function getPreviousPhoto($photo)
    {
        if (!$this->isAllowed('view')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('Not allowed to view photos')
            );
        }

        return $this->getPhotoMapper()->getPreviousPhoto($photo);
    }

    /**
     * Get all photos in an album
     *
     * @param \Photo\Model\Album $album the album to get the photos from
     * @param integer $start the result to start at
     * @param integer $maxResults max amount of results to return, null for infinite
     *
     * @return array of Photo\Model\Album
     */
    public function getPhotos($album, $start = 0, $maxResults = null)
    {
        if (!$this->isAllowed('view')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('Not allowed to view photos')
            );
        }

        return $this->getPhotoMapper()->getAlbumPhotos($album, $start, $maxResults);
    }

    /**
     * Returns a unique file name for a photo.
     *
     * @param \Photo\Model\Photo $photo the photo to get a name for
     *
     * @return string
     */
    public function getPhotoFileName($photo)
    {
        // filtering is required to prevent invalid characters in file names.
        $filter = new \Zend\I18n\Filter\Alnum(true);
        $albumName = $filter->filter($photo->getAlbum()->getName());

        // don't put spaces in file names
        $albumName = str_replace(' ', '-', $albumName);

        $extension = substr($photo->getPath(), strpos($photo->getPath(), '.'));

        $photoName = $albumName . '-' . $photo->getDateTime()->format('Y') . '-' . $photo->getId() . $extension;

        return $photoName;
    }

    /**
     * Returns a zend response to be used for downloading a photo.
     *
     * @param integer $photoId
     * @return \Zend\Http\Response\Stream
     */
    public function getPhotoDownload($photoId)
    {
        if (!$this->isAllowed('download')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('Not allowed to download photos')
            );
        }

        $photo = $this->getPhoto($photoId);
        $path =  $photo->getPath();
        $fileName = $this->getPhotoFileName($photo);

        return $this->getFileStorageService()->downloadFile($path, $fileName);
    }

    /**
     * Get the photo data belonging to a certain photo
     *
     * @param int $photoId the id of the photo to retrieve
     *
     * @return array|null of data about the photo, which is useful inside a view
     *          or null if the photo was not found
     */
    public function getPhotoData($photoId)
    {
        if (!$this->isAllowed('view')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('Not allowed to view photos')
            );
        }

        $photo = $this->getPhoto($photoId);

        // photo does not exist
        if (is_null($photo)) {
            return null;
        }

        $next = $this->getNextPhoto($photo);
        $previous = $this->getPreviousPhoto($photo);

        return [
            'photo' => $photo,
            'next' => $next,
            'previous' => $previous
        ];
    }


    /**
     * Removes a photo from the database and deletes its files, including thumbs
     * from the server.
     *
     * @param int $photoId the id of the photo to delete
     *
     * @return bool indicating whether the delete was successful
     */
    public function deletePhoto($photoId)
    {
        if (!$this->isAllowed('delete')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('Not allowed to delete photos.')
            );
        }

        $photo = $this->getPhoto($photoId);
        if (is_null($photo)) {
            return false;
        }
        $this->getPhotoMapper()->remove($photo);
        $this->getPhotoMapper()->flush();

        return true;

    }

    /**
     * Deletes a stored photo at a given path.
     *
     * @param string $path
     * @return bool indicated whether deleting the photo was successful.
     */
    public function deletePhotoFile($path)
    {
        return $this->getFileStorageService()->removeFile($path);

    }

    /**
     * Deletes all files associated with a photo.
     *
     * @param \Photo\Model\Photo $photo
     */
    public function deletePhotoFiles($photo)
    {
        $this->deletePhotoFile($photo->getPath());
        $this->deletePhotoFile($photo->getLargeThumbPath());
        $this->deletePhotoFile($photo->getSmallThumbPath());
    }

    /**
     * Moves a photo to a new album.
     *
     * @param int $photoId the id of the photo
     * @param int $albumId the id of the new album
     *
     * @return bool indicating whether move was successful
     */
    public function movePhoto($photoId, $albumId)
    {
        if (!$this->isAllowed('move')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('Not allowed to move photos')
            );
        }

        $photo = $this->getPhoto($photoId);
        $album = $this->getAlbumService()->getAlbum($albumId);
        if (is_null($photo) || is_null($album)) {
            return false;
        }

        $photo->setAlbum($album);
        $this->getAlbumMapper()->flush();

        return true;

    }
    /**
     * Generates the PhotoOfTheWeek and adds it to the list
     * if at least one photo has been viewed in the specified time.
     * The parameters determine the week to check the photos of.
     *
     * @param \DateTime $begindate
     * @param \DateTime $enddate
     *
     * @return \Photo\Model\Photo|null
     */
    public function generatePhotoOfTheWeek($begindate = null, $enddate = null)
    {
        if(is_null($begindate) || is_null($enddate)) {
            $begindate = (new \DateTime())->sub(new \DateInterval('P1W'));
            $enddate = new \DateTime();
        }
        $bestPhoto = $this->determinePhotoOfTheWeek($begindate, $enddate);
        if (is_null($bestPhoto)) {
            return null;
        }
        $weeklyPhoto = new WeeklyPhotoModel();
        $weeklyPhoto->setPhoto($bestPhoto);
        $weeklyPhoto->setWeek($begindate);
        $mapper = $this->getWeeklyPhotoMapper();
        $mapper->persist($weeklyPhoto);
        $mapper->flush();
        return $weeklyPhoto;
    }

    /**
     * Determine which photo is the photo of the week
     *
     * @param \DateTime $begindate
     * @param \DateTime $enddate
     * @return \Photo\Model\Photo|null
     */
    public function determinePhotoOfTheWeek($begindate, $enddate)
    {
        $results = $this->getHitMapper()->getHitsInRange($begindate, $enddate);
        if (empty($results)){
            return null;
        }
        $bestRating = -1;
        $bestPhoto = null;
        foreach ($results as $res){
            $photo = $this->getPhotoMapper()->getPhotoById($res[1]);
            $rating = $this->ratePhoto($photo, $res[2]);
            if (!$this->getWeeklyPhotoMapper()->hasBeenPhotoOfTheWeek($photo) && $rating > $bestRating) {
                $bestPhoto = $photo;
                $bestRating = $rating;
            }
        }
        return $bestPhoto;
    }

    /**
     * Retrieves all WeeklyPhotos
     *
     * @return array
     */
    public function getPhotosOfTheWeek()
    {
        if (!$this->isAllowed('view')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('Not allowed to view previous photos of the week')
            );
        }
        return $this->getWeeklyPhotoMapper()->getPhotosOfTheWeek();
    }

    /**
     * Determine the preference rating of the photo.
     *
     * @param \Photo\Model\Photo $photo
     * @param integer $occurences
     * @return float
     */
    public function ratePhoto($photo, $occurences)
    {
        $tagged = $photo->getTags()->count() > 0;
        $now = new \DateTime();
        $age = $now->diff($photo->getDateTime(), true)->days;
        $res = $occurences * (1 + 1 / $age);
        return $tagged ? 1.5 * $res : $res;
    }

    public function getCurrentPhotoOfTheWeek()
    {
        return $this->getWeeklyPhotoMapper()->getCurrentPhotoOfTheWeek();
    }

    /**
     * Count a hit for the specified photo. Should be called whenever a photo is viewed.
     *
     * @param \Photo\Model\Photo $photo
     */
    public function countHit($photo)
    {
        $hit = new HitModel();
        $hit->setDateTime(new \DateTime());
        $photo->addHit($hit);

        $this->getPhotoMapper()->flush();
    }

    /**
     * Retrieves a tag if it exists.
     *
     * @param integer $photoId
     * @param integer $lidnr
     *
     * @return \Photo\Model\Tag|null
     */
    public function findTag($photoId, $lidnr)
    {
        if (!$this->isAllowed('view', 'tag')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('Not allowed to view tags.')
            );
        }

        return $this->getTagMapper()->findTag($photoId, $lidnr);
    }

    /**
     * Tags a user in the specified photo.
     *
     * @param integer $photoId
     * @param integer $lidnr
     *
     * @return \Photo\Model\Tag|null
     */
    public function addTag($photoId, $lidnr)
    {
        if (!$this->isAllowed('add', 'tag')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('Not allowed to add tags.')
            );
        }

        if (is_null($this->findTag($photoId, $lidnr))) {
            $photo = $this->getPhoto($photoId);
            $member = $this->getMemberService()->findMemberByLidnr($lidnr);
            $tag = new TagModel();
            $tag->setMember($member);
            $photo->addTag($tag);

            $this->getPhotoMapper()->flush();

            return $tag;
        } else {
            // Tag exists
            return null;
        }
    }

    /**
     * Removes a tag
     *
     * @param integer $photoId
     * @param integer $lidnr
     *
     * @return boolean indicating whether removing the tag succeeded.
     */
    public function removeTag($photoId, $lidnr)
    {
        if (!$this->isAllowed('remove', 'tag')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('Not allowed to remove tags.')
            );
        }

        $tag = $this->findTag($photoId, $lidnr);
        if (!is_null($tag)) {
            $this->getTagMapper()->remove($tag);
            $this->getTagMapper()->flush();

            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets all photos in which a member has been tagged.
     *
     * @param \Decision\Model\Member $member
     *
     * @return array
     */
    public function getTagsForMember($member)
    {
        if (!$this->isAllowed('view', 'tag')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('Not allowed to view tags.')
            );
        }

        return $this->getTagMapper()->getTagsByLidnr($member->getLidnr());
    }

    /**
     * Gets the base directory from which the photo paths should be requested
     *
     * @return string
     */
    public function getBaseDirectory()
    {
        $config = $this->getConfig();

        return str_replace('public', '', $config['upload_dir']);
    }

    /**
     * Get the photo config, as used by this service.
     *
     * @return array containing the config for the module
     */
    public function getConfig()
    {
        $config = $this->sm->get('config');

        return $config['photo'];
    }

    /**
     * Get the storage config, as used by this service.
     *
     * @return array containing the config for the module
     */
    public function getStorageConfig()
    {
        $config = $this->sm->get('config');

        return $config['storage'];
    }

    /**
     * Get the photo mapper.
     *
     * @return \Photo\Mapper\Photo
     */
    public function getPhotoMapper()
    {
        return $this->sm->get('photo_mapper_photo');
    }

    /**
     * Get the album mapper.
     *
     * @return \Photo\Mapper\Album
     */
    public function getAlbumMapper()
    {
        return $this->sm->get('photo_mapper_album');
    }

    /**
     * Get the tag mapper.
     *
     * @return \Photo\Mapper\Tag
     */
    public function getTagMapper()
    {
        return $this->sm->get('photo_mapper_tag');
    }

    public function getHitMapper()
    {
        return $this->sm->get('photo_mapper_hit');
    }

    /**
     * Get the weekly photo mapper.
     *
     * @return \Photo\Mapper\WeeklyPhoto
     */
    public function getWeeklyPhotoMapper()
    {
        return $this->sm->get('photo_mapper_weekly_photo');
    }
    /**
     * Gets the metadata service.
     *
     * @return \Photo\Service\Metadata
     */
    public function getMetadataService()
    {
        return $this->sm->get('photo_service_metadata');
    }

    /**
     * Gets the album service.
     *
     * @return \Photo\Service\Album
     */
    public function getAlbumService()
    {
        return $this->sm->get('photo_service_album');
    }

    /**
     * Get the member service.
     *
     * @return \Decision\Service\Member
     */
    public function getMemberService()
    {
        return $this->sm->get('decision_service_member');
    }

    /**
     * Gets the storage service.
     *
     * @return \Application\Service\Storage
     */
    public function getFileStorageService()
    {
        return $this->sm->get('application_service_storage');
    }

    /**
     * Get the default resource ID.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'photo';
    }

    /**
     * Get the Acl.
     *
     * @return \Zend\Permissions\Acl\Acl
     */
    public function getAcl()
    {
        return $this->sm->get('photo_acl');
    }
}
