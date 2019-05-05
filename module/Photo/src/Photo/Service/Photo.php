<?php

namespace Photo\Service;

use Application\Service\AbstractAclService;
use Application\Service\Storage;
use DateTime;
use DateInterval;
use Decision\Model\Member;
use Exception;
use Photo\Model\Hit as HitModel;
use Photo\Model\Tag as TagModel;
use Photo\Mapper\Tag as TagMapper;
use Photo\Model\Photo as PhotoModel;
use Photo\Model\WeeklyPhoto as WeeklyPhotoModel;
use Photo\Mapper\WeeklyPhoto as WeeklyPhotoMapper;
use Photo\Model\ProfilePhoto as ProfilePhotoModel;
use Photo\Mapper\ProfilePhoto as ProfilePhotoMapper;
use Photo\Model\Album as AlbumModel;
use User\Permissions\NotAllowedException;
use Zend\Http\Response\Stream;
use Zend\I18n\Filter\Alnum;
use Zend\Permissions\Acl\Acl;

/**
 * Photo service.
 */
class Photo extends AbstractAclService
{
    /**
     * Get all photos in an album
     *
     * @param AlbumModel $album the album to get the photos from
     * @param integer $start the result to start at
     * @param integer $maxResults max amount of results to return,
     *                                       null for infinite
     *
     * @return array of Photo\Model\Album
     */
    public function getPhotos($album, $start = 0, $maxResults = null)
    {
        if (!$this->isAllowed('view')) {
            throw new NotAllowedException(
                $this->getTranslator()->translate('Not allowed to view photos')
            );
        }

        return $this->getPhotoMapper()->getAlbumPhotos($album, $start,
            $maxResults);
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
     * Returns a zend response to be used for downloading a photo.
     *
     * @param integer $photoId
     *
     * @return Stream
     */
    public function getPhotoDownload($photoId, $options)
    {
        if (!$this->isAllowed('download')) {
            throw new NotAllowedException(
                $this->getTranslator()
                    ->translate('Not allowed to download photos')
            );
        }

        $photo = $this->getPhoto($photoId);
        $path = $photo->getPath();
        $fileName = $this->getPhotoFileName($photo);
        $server = $this->sm->get('photo_glide_server');

        return $server->outputImage($path, $options);
    }

    /**
     * Retrieves a photo by an id.
     *
     * @param integer $id the id of the album
     *
     * @return PhotoModel photo matching the given id
     */
    public function getPhoto($id)
    {
        if (!$this->isAllowed('view')) {
            throw new NotAllowedException(
                $this->getTranslator()->translate('Not allowed to view photos')
            );
        }

        return $this->getPhotoMapper()->getPhotoById($id);
    }

    /**
     * Returns a unique file name for a photo.
     *
     * @param PhotoModel $photo the photo to get a name for
     *
     * @return string
     */
    public function getPhotoFileName($photo)
    {
        // filtering is required to prevent invalid characters in file names.
        $filter = new Alnum(true);
        $albumName = $filter->filter($photo->getAlbum()->getName());

        // don't put spaces in file names
        $albumName = str_replace(' ', '-', $albumName);

        $extension = substr($photo->getPath(), strpos($photo->getPath(), '.'));

        $photoName = $albumName . '-' . $photo->getDateTime()->format('Y') . '-'
            . $photo->getId() . $extension;

        return $photoName;
    }

    /**
     * Gets the storage service.
     *
     * @return Storage
     */
    public function getFileStorageService()
    {
        return $this->sm->get('application_service_storage');
    }

    /**
     * Get the photo data belonging to a certain photo
     *
     * @param int $photoId the id of the photo to retrieve
     *
     * @param AlbumModel|null $album
     * @return array|null of data about the photo, which is useful inside a view
     *          or null if the photo was not found
     * @throws Exception
     */
    public function getPhotoData($photoId, AlbumModel $album = null)
    {
        if (!$this->isAllowed('view')) {
            throw new NotAllowedException(
                $this->getTranslator()->translate('Not allowed to view photos')
            );
        }

        $photo = $this->getPhoto($photoId);

        // photo does not exist
        if (is_null($photo)) {
            return null;
        }

        if (is_null($album)) {
            // Default type for albums is Album.
            $album = new AlbumModel();
        }

        $next = $this->getNextPhoto($photo, $album);
        $previous = $this->getPreviousPhoto($photo, $album);

        $lidnr = $this->getMemberService()->getRole()->getLidnr();
        $isTagged = $this->isTaggedIn($photoId, $lidnr);
        $profilePhoto = $this->getStoredProfilePhoto($lidnr);
        $isProfilePhoto = false;
        $isExplicitProfilePhoto = false;
        if ($profilePhoto != null) {
            $isExplicitProfilePhoto = $profilePhoto->isExplicit();
            if ($photoId == $profilePhoto->getPhoto()->getId()) {
                $isProfilePhoto = true;
            }
        }

        return [
            'photo' => $photo,
            'next' => $next,
            'previous' => $previous,
            'isTagged' => $isTagged,
            'isProfilePhoto' => $isProfilePhoto,
            'isExplicitProfilePhoto' => $isExplicitProfilePhoto
        ];
    }

    /**
     * Returns the next photo in the album to display
     *
     * @param PhotoModel $photo
     *
     * @param AlbumModel $album
     * @return PhotoModel The next photo.
     */
    public function getNextPhoto(
        PhotoModel $photo,
        AlbumModel $album
    ) {
        if (!$this->isAllowed('view')) {
            throw new NotAllowedException(
                $this->getTranslator()->translate('Not allowed to view photos')
            );
        }

        return $this->getPhotoMapper()->getNextPhoto($photo, $album);
    }

    /**
     * Returns the previous photo in the album to display
     *
     * @param PhotoModel $photo
     *
     * @param AlbumModel $album
     * @return PhotoModel The next photo.
     */
    public function getPreviousPhoto(
        PhotoModel $photo,
        AlbumModel $album
    ) {
        if (!$this->isAllowed('view')) {
            throw new NotAllowedException(
                $this->getTranslator()->translate('Not allowed to view photos')
            );
        }

        return $this->getPhotoMapper()->getPreviousPhoto($photo, $album);
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
            throw new NotAllowedException(
                $this->getTranslator()
                    ->translate('Not allowed to delete photos.')
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
     * Deletes all files associated with a photo.
     *
     * @param PhotoModel $photo
     */
    public function deletePhotoFiles($photo)
    {
        $this->deletePhotoFile($photo->getPath());
        $this->deletePhotoFile($photo->getLargeThumbPath());
        $this->deletePhotoFile($photo->getSmallThumbPath());
    }

    /**
     * Deletes a stored photo at a given path.
     *
     * @param string $path
     *
     * @return bool indicated whether deleting the photo was successful.
     */
    public function deletePhotoFile($path)
    {
        return $this->getFileStorageService()->removeFile($path);

    }

    /**
     * Moves a photo to a new album.
     *
     * @param int $photoId the id of the photo
     * @param int $albumId the id of the new album
     *
     * @return bool indicating whether move was successful
     * @throws Exception
     */
    public function movePhoto($photoId, $albumId)
    {
        if (!$this->isAllowed('move')) {
            throw new NotAllowedException(
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
     * Gets the album service.
     *
     * @return Photo\Service\Album
     */
    public function getAlbumService()
    {
        return $this->sm->get('photo_service_album');
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
     * Generates the PhotoOfTheWeek and adds it to the list
     * if at least one photo has been viewed in the specified time.
     * The parameters determine the week to check the photos of.
     *
     * @param DateTime $begindate
     * @param DateTime $enddate
     *
     * @return PhotoModel|null
     */
    public function generatePhotoOfTheWeek($begindate = null, $enddate = null)
    {
        if (is_null($begindate) || is_null($enddate)) {
            $begindate = (new DateTime())->sub(new DateInterval('P1W'));
            $enddate = new DateTime();
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
     * @param DateTime $begindate
     * @param DateTime $enddate
     *
     * @return PhotoModel|null
     */
    public function determinePhotoOfTheWeek($begindate, $enddate)
    {
        $results = $this->getHitMapper()->getHitsInRange($begindate, $enddate);
        if (empty($results)) {
            return null;
        }
        $bestRating = -1;
        $bestPhoto = null;
        foreach ($results as $res) {
            $photo = $this->getPhotoMapper()->getPhotoById($res[1]);
            $rating = $this->ratePhoto($photo, $res[2]);
            if (!$this->getWeeklyPhotoMapper()->hasBeenPhotoOfTheWeek($photo)
                && $rating > $bestRating
            ) {
                $bestPhoto = $photo;
                $bestRating = $rating;
            }
        }

        return $bestPhoto;
    }

    /**
     * Determine which photo is best suited as profile picture
     *
     * @param int $lidnr
     *
     * @return PhotoModel|null
     * @throws Exception
     */
    public function getProfilePhoto($lidnr)
    {
        $profilePhoto = $this->getStoredProfilePhoto($lidnr);
        if ($profilePhoto != null) {
            return $profilePhoto->getPhoto();
        }

        return $this->determineProfilePhoto($lidnr);
    }

    /**
     * Determine which photo is best suited as profile picture
     *
     * @param int $lidnr
     *
     * @return ProfilePhotoModel|null
     * @throws Exception
     */
    private function getStoredProfilePhoto($lidnr)
    {
        $profilePhoto = $this->getProfilePhotoMapper()->getProfilePhotoByLidnr($lidnr);
        if ($profilePhoto != null) {
            if ($profilePhoto->getDateTime() < new DateTime()) {
                $this->removeProfilePhoto($profilePhoto);
                return null;
            }
            if (!$this->isTaggedIn($profilePhoto->getPhoto()->getId(), $lidnr)) {
                $this->removeProfilePhoto($profilePhoto);
                return null;
            }
            return $profilePhoto;
        }

        return null;
    }

    /**
     * @param ProfilePhotoModel $profilePhoto
     * @throws Exception
     */
    public function removeProfilePhoto(ProfilePhotoModel $profilePhoto = null)
    {
        if ($profilePhoto == null) {
            $member = $this->getMemberService()->getRole()->getMember();
            $lidnr = $member->getLidnr();
            $profilePhoto = $this->getStoredProfilePhoto($lidnr);
        }
        if ($profilePhoto != null) {
            $mapper = $this->getProfilePhotoMapper();
            $mapper->remove($profilePhoto);
            $mapper->flush();
        }
    }

    /**
     * @param int $lidnr
     * @return PhotoModel|null
     * @throws Exception
     */
    private function determineProfilePhoto($lidnr)
    {
        $results = $this->getTagMapper()->getTagsByLidnr($lidnr);

        if (empty($results)) {
            return null;
        }

        $bestRating = -1;
        $bestPhoto = null;

        foreach ($results as $res) {
            $photo = $res->getPhoto();
            $rating = $this->ratePhotoForMember($photo);
            if ($rating > $bestRating) {
                $bestPhoto = $photo;
                $bestRating = $rating;
            }
        }

        $this->cacheProfilePhoto($lidnr, $bestPhoto);

        return $bestPhoto;
    }

    /**
     * @param int $lidnr
     * @param PhotoModel $photo
     * @throws Exception
     */
    private function cacheProfilePhoto($lidnr, PhotoModel $photo)
    {
        $member = $this->getMemberService()->findMemberByLidnr($lidnr);
        $now = new DateTime();
        if ($member->isActive()) {
            $dateTime = $now->add(new DateInterval('P1D'));
        } else {
            $dateTime = $now->add(new DateInterval('P5D'));
        }

        $this->storeProfilePhoto($photo, $member, $dateTime);
    }

    /**
     * @param PhotoModel $photo
     * @param Member $member
     * @param DateTime $dateTime
     * @param bool $explicit
     */
    private function storeProfilePhoto(PhotoModel $photo, Member $member, $dateTime, $explicit = false)
    {
        if (!$this->isTaggedIn($photo->getId(), $member->getLidnr())) {
            return;
        }
        $profilePhotoModel = new ProfilePhotoModel();
        $profilePhotoModel->setMember($member);
        $profilePhotoModel->setPhoto($photo);
        $profilePhotoModel->setDatetime($dateTime);
        $profilePhotoModel->setExplicit($explicit);
        $mapper = $this->getProfilePhotoMapper();
        $mapper->persist($profilePhotoModel);
        $mapper->flush();
    }

    /**
     * @param int $photoId
     * @throws Exception
     */
    public function setProfilePhoto($photoId)
    {
        $photo = $this->getPhoto($photoId);
        $member = $this->getMemberService()->getRole()->getMember();
        $lidnr = $member->getLidnr();
        $profilePhoto = $this->getStoredProfilePhoto($lidnr);
        if ($profilePhoto != null) {
            $this->removeProfilePhoto($profilePhoto);
        }
        $dateTime = (new DateTime())->add(new DateInterval('P1Y'));
        $this->storeProfilePhoto($photo, $member, $dateTime, true);
    }

    /**
     * @param int $lidnr
     * @return bool
     * @throws Exception
     */
    public function hasExplicitProfilePhoto($lidnr)
    {
        $profilePhoto = $this->getStoredProfilePhoto($lidnr);
        if ($profilePhoto != null) {
            return $profilePhoto->isExplicit();
        }
        return false;
    }

    public function getHitMapper()
    {
        return $this->sm->get('photo_mapper_hit');
    }

    /**
     * Determine the preference rating of the photo.
     *
     * @param PhotoModel $photo
     * @param integer $occurences
     *
     * @return float
     * @throws Exception
     */
    public function ratePhoto($photo, $occurences)
    {
        $tagged = $photo->getTags()->count() > 0;
        $now = new DateTime();
        $age = $now->diff($photo->getDateTime(), true)->days;
        $res = $occurences * (1 + 1 / $age);

        return $tagged ? 1.5 * $res : $res;
    }

    /**
     * Determine the preference rating of the photo.
     *
     * @param PhotoModel $photo
     *
     * @return float
     * @throws Exception
     */
    public function ratePhotoForMember($photo)
    {
        $now = new DateTime();
        $age = $now->diff($photo->getDateTime(), true)->days;

        $hits = $photo->getHitCount();
        $tags = $photo->getTagCount();

        $baseRating = $hits / pow($tags, 1.25);
        // Prevent division by zero.
        if ($age < 14) {
            return $baseRating * (14 - $age);
        }
        return $baseRating / $age;
    }

    /**
     * Get the weekly photo mapper.
     *
     * @return WeeklyPhotoMapper
     */
    public function getWeeklyPhotoMapper()
    {
        return $this->sm->get('photo_mapper_weekly_photo');
    }

    /**
     * Retrieves all WeeklyPhotos
     *
     * @return array
     */
    public function getPhotosOfTheWeek()
    {
        if (!$this->isAllowed('view')) {
            throw new NotAllowedException(
                $this->getTranslator()
                    ->translate('Not allowed to view previous photos of the week')
            );
        }

        return $this->getWeeklyPhotoMapper()->getPhotosOfTheWeek();
    }

    public function getCurrentPhotoOfTheWeek()
    {
        return $this->getWeeklyPhotoMapper()->getCurrentPhotoOfTheWeek();
    }

    /**
     * Count a hit for the specified photo. Should be called whenever a photo
     * is viewed.
     *
     * @param PhotoModel $photo
     */
    public function countHit($photo)
    {
        $hit = new HitModel();
        $hit->setDateTime(new DateTime());
        $photo->addHit($hit);

        $this->getPhotoMapper()->flush();
    }

    /**
     * Tags a user in the specified photo.
     *
     * @param integer $photoId
     * @param integer $lidnr
     *
     * @return Photo\Model\Tag|null
     */
    public function addTag($photoId, $lidnr)
    {
        if (!$this->isAllowed('add', 'tag')) {
            throw new NotAllowedException(
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
     * Retrieves a tag if it exists.
     *
     * @param integer $photoId
     * @param integer $lidnr
     *
     * @return Photo\Model\Tag|null
     */
    public function findTag($photoId, $lidnr)
    {
        if (!$this->isAllowed('view', 'tag')) {
            throw new NotAllowedException(
                $this->getTranslator()->translate('Not allowed to view tags.')
            );
        }

        return $this->getTagMapper()->findTag($photoId, $lidnr);
    }


    /**
     * Checks whether a user is tagged in a photo
     *
     * @param integer $photoId
     * @param integer $lidnr
     *
     * @return bool
     */
    public function isTaggedIn($photoId, $lidnr)
    {
        $tag = $this->findTag($photoId, $lidnr);
        if ($tag != null) {
            return true;
        }
        return false;
    }

    /**
     * Get the tag mapper.
     *
     * @return TagMapper
     */
    public function getTagMapper()
    {
        return $this->sm->get('photo_mapper_tag');
    }

    /**
     * Get the tag mapper.
     *
     * @return ProfilePhotoMapper
     */
    public function getProfilePhotoMapper()
    {
        return $this->sm->get('photo_mapper_profile_photo');
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
            throw new NotAllowedException(
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
     * @param Member $member
     *
     * @return array
     */
    public function getTagsForMember($member)
    {
        if (!$this->isAllowed('view', 'tag')) {
            throw new NotAllowedException(
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
     * Gets the metadata service.
     *
     * @return Photo\Service\Metadata
     */
    public function getMetadataService()
    {
        return $this->sm->get('photo_service_metadata');
    }

    /**
     * Get the Acl.
     *
     * @return Acl
     */
    public function getAcl()
    {
        return $this->sm->get('photo_acl');
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
}
