<?php

namespace Photo\Service;

use Application\Service\AbstractAclService;
use Application\Service\FileStorage;
use DateInterval;
use DateTime;
use Decision\Model\Member;
use Exception;
use Laminas\Http\Response\Stream;
use Laminas\I18n\Filter\Alnum;
use Laminas\Mvc\I18n\Translator;
use Laminas\Permissions\Acl\Acl;
use Photo\Mapper\Hit;
use Photo\Mapper\ProfilePhoto as ProfilePhotoMapper;
use Photo\Mapper\Tag;
use Photo\Mapper\Tag as TagMapper;
use Photo\Mapper\Vote;
use Photo\Mapper\WeeklyPhoto as WeeklyPhotoMapper;
use Photo\Model\Album as AlbumModel;
use Photo\Model\Hit as HitModel;
use Photo\Model\Photo as PhotoModel;
use Photo\Model\ProfilePhoto as ProfilePhotoModel;
use Photo\Model\Tag as TagModel;
use Photo\Model\Vote as VoteModel;
use Photo\Model\WeeklyPhoto as WeeklyPhotoModel;
use User\Model\User;
use User\Permissions\NotAllowedException;

/**
 * Photo service.
 */
class Photo extends AbstractAclService
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var User|string
     */
    private $userRole;

    /**
     * @var Acl
     */
    private $acl;

    /**
     * @var \Decision\Service\Member
     */
    private $memberService;

    /**
     * @var FileStorage
     */
    private $storageService;

    /**
     * @var \Photo\Mapper\Photo
     */
    private $photoMapper;

    /**
     * @var \Photo\Mapper\Album
     */
    private $albumMapper;

    /**
     * @var TagMapper
     */
    private $tagMapper;

    /**
     * @var Hit
     */
    private $hitMapper;

    /**
     * @var Vote
     */
    private $voteMapper;

    /**
     * @var WeeklyPhotoMapper
     */
    private $weeklyPhotoMapper;

    /**
     * @var ProfilePhotoMapper
     */
    private $profilePhotoMapper;

    /**
     * @var array
     */
    private $photoConfig;

    public function __construct(
        Translator $translator,
        $userRole,
        Acl $acl,
        \Decision\Service\Member $memberService,
        FileStorage $storageService,
        \Photo\Mapper\Photo $photoMapper,
        \Photo\Mapper\Album $albumMapper,
        Tag $tagMapper,
        Hit $hitMapper,
        Vote $voteMapper,
        WeeklyPhotoMapper $weeklyPhotoMapper,
        ProfilePhotoMapper $profilePhotoMapper,
        array $photoConfig
    )
    {
        $this->translator = $translator;
        $this->userRole = $userRole;
        $this->acl = $acl;
        $this->memberService = $memberService;
        $this->storageService = $storageService;
        $this->photoMapper = $photoMapper;
        $this->albumMapper = $albumMapper;
        $this->tagMapper = $tagMapper;
        $this->hitMapper = $hitMapper;
        $this->voteMapper = $voteMapper;
        $this->weeklyPhotoMapper = $weeklyPhotoMapper;
        $this->profilePhotoMapper = $profilePhotoMapper;
        $this->photoConfig = $photoConfig;
    }

    public function getRole()
    {
        return $this->userRole;
    }

    /**
     * Get all photos in an album.
     *
     * @param AlbumModel $album the album to get the photos from
     * @param int $start the result to start at
     * @param int $maxResults max amount of results to return,
     *                               null for infinite
     *
     * @return array of Photo\Model\Album
     */
    public function getPhotos($album, $start = 0, $maxResults = null)
    {
        if (!$this->isAllowed('view')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to view photos'));
        }

        return $this->photoMapper->getAlbumPhotos(
            $album,
            $start,
            $maxResults
        );
    }

    /**
     * Returns a zend response to be used for downloading a photo.
     *
     * @param int $photoId
     *
     * @return Stream
     */
    public function getPhotoDownload($photoId)
    {
        if (!$this->isAllowed('download')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to download photos'));
        }

        $photo = $this->getPhoto($photoId);
        $path = $photo->getPath();
        $fileName = $this->getPhotoFileName($photo);

        return $this->storageService->downloadFile($path, $fileName);
    }

    /**
     * Retrieves a photo by an id.
     *
     * @param int $id the id of the album
     *
     * @return PhotoModel photo matching the given id
     */
    public function getPhoto($id)
    {
        if (!$this->isAllowed('view')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to view photos'));
        }

        return $this->photoMapper->getPhotoById($id);
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

        return $albumName . '-' . $photo->getDateTime()->format('Y') . '-'
            . $photo->getId() . $extension;
    }

    /**
     * Get the photo data belonging to a certain photo.
     *
     * @param int $photoId the id of the photo to retrieve
     *
     * @return array|null of data about the photo, which is useful inside a view
     *                    or null if the photo was not found
     *
     * @throws Exception
     */
    public function getPhotoData($photoId, AlbumModel $album = null)
    {
        if (!$this->isAllowed('view')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to view photos'));
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

        $lidnr = $this->memberService->getRole()->getLidnr();
        $isTagged = $this->isTaggedIn($photoId, $lidnr);
        $profilePhoto = $this->getStoredProfilePhoto($lidnr);
        $isProfilePhoto = false;
        $isExplicitProfilePhoto = false;
        if (null != $profilePhoto) {
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
            'isExplicitProfilePhoto' => $isExplicitProfilePhoto,
        ];
    }

    /**
     * Returns the next photo in the album to display.
     *
     * @return PhotoModel the next photo
     */
    public function getNextPhoto(
        PhotoModel $photo,
        AlbumModel $album
    )
    {
        if (!$this->isAllowed('view')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to view photos'));
        }

        return $this->photoMapper->getNextPhoto($photo, $album);
    }

    /**
     * Returns the previous photo in the album to display.
     *
     * @return PhotoModel the next photo
     */
    public function getPreviousPhoto(
        PhotoModel $photo,
        AlbumModel $album
    )
    {
        if (!$this->isAllowed('view')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to view photos'));
        }

        return $this->photoMapper->getPreviousPhoto($photo, $album);
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
            throw new NotAllowedException($this->translator->translate('Not allowed to delete photos.'));
        }

        $photo = $this->getPhoto($photoId);
        if (is_null($photo)) {
            return false;
        }
        $this->photoMapper->remove($photo);
        $this->photoMapper->flush();

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
     * @return bool indicated whether deleting the photo was successful
     */
    public function deletePhotoFile($path)
    {
        return $this->storageService->removeFile($path);
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
        $mapper = $this->weeklyPhotoMapper;
        $mapper->persist($weeklyPhoto);
        $mapper->flush();

        return $weeklyPhoto;
    }

    /**
     * Determine which photo is the photo of the week.
     *
     * @param DateTime $begindate
     * @param DateTime $enddate
     *
     * @return PhotoModel|null
     */
    public function determinePhotoOfTheWeek($begindate, $enddate)
    {
        $results = $this->hitMapper->getHitsInRange($begindate, $enddate);
        if (empty($results)) {
            return null;
        }
        $bestRating = -1;
        $bestPhoto = null;
        foreach ($results as $res) {
            $photo = $this->photoMapper->getPhotoById($res[1]);
            $rating = $this->ratePhoto($photo, $res[2]);
            if (
                !$this->weeklyPhotoMapper->hasBeenPhotoOfTheWeek($photo)
                && $rating > $bestRating
            ) {
                $bestPhoto = $photo;
                $bestRating = $rating;
            }
        }

        return $bestPhoto;
    }

    /**
     * Determine which photo is best suited as profile picture.
     *
     * @param int $lidnr
     *
     * @return PhotoModel|null
     *
     * @throws Exception
     */
    public function getProfilePhoto($lidnr)
    {
        $profilePhoto = $this->getStoredProfilePhoto($lidnr);
        if (null != $profilePhoto) {
            return $profilePhoto->getPhoto();
        }

        return $this->determineProfilePhoto($lidnr);
    }

    /**
     * Determine which photo is best suited as profile picture.
     *
     * @param int $lidnr
     *
     * @return ProfilePhotoModel|null
     *
     * @throws Exception
     */
    private function getStoredProfilePhoto($lidnr)
    {
        $profilePhoto = $this->profilePhotoMapper->getProfilePhotoByLidnr($lidnr);
        if (null != $profilePhoto) {
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
     *
     * @throws Exception
     */
    public function removeProfilePhoto(ProfilePhotoModel $profilePhoto = null)
    {
        if (null == $profilePhoto) {
            $member = $this->memberService->getRole()->getMember();
            $lidnr = $member->getLidnr();
            $profilePhoto = $this->getStoredProfilePhoto($lidnr);
        }
        if (null != $profilePhoto) {
            $mapper = $this->profilePhotoMapper;
            $mapper->remove($profilePhoto);
            $mapper->flush();
        }
    }

    /**
     * @param int $lidnr
     *
     * @return PhotoModel|null
     *
     * @throws Exception
     */
    private function determineProfilePhoto($lidnr)
    {
        $results = $this->tagMapper->getTagsByLidnr($lidnr);

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
     *
     * @throws Exception
     */
    private function cacheProfilePhoto($lidnr, PhotoModel $photo)
    {
        $member = $this->memberService->findMemberByLidnr($lidnr);
        $now = new DateTime();
        if ($member->isActive()) {
            $dateTime = $now->add(new DateInterval('P1D'));
        } else {
            $dateTime = $now->add(new DateInterval('P5D'));
        }

        $this->storeProfilePhoto($photo, $member, $dateTime);
    }

    /**
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
        $mapper = $this->profilePhotoMapper;
        $mapper->persist($profilePhotoModel);
        $mapper->flush();
    }

    /**
     * @param int $photoId
     *
     * @throws Exception
     */
    public function setProfilePhoto($photoId)
    {
        $photo = $this->getPhoto($photoId);
        $member = $this->memberService->getRole()->getMember();
        $lidnr = $member->getLidnr();
        $profilePhoto = $this->getStoredProfilePhoto($lidnr);
        if (null != $profilePhoto) {
            $this->removeProfilePhoto($profilePhoto);
        }
        $dateTime = (new DateTime())->add(new DateInterval('P1Y'));
        $this->storeProfilePhoto($photo, $member, $dateTime, true);
    }

    /**
     * @param int $lidnr
     *
     * @return bool
     *
     * @throws Exception
     */
    public function hasExplicitProfilePhoto($lidnr)
    {
        $profilePhoto = $this->getStoredProfilePhoto($lidnr);
        if (null != $profilePhoto) {
            return $profilePhoto->isExplicit();
        }

        return false;
    }

    /**
     * Determine the preference rating of the photo.
     *
     * @param PhotoModel $photo
     * @param int $occurences
     *
     * @return float
     *
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
     *
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
     * Retrieves all WeeklyPhotos.
     *
     * @return array
     */
    public function getPhotosOfTheWeek()
    {
        if (!$this->isAllowed('view')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to view previous photos of the week'));
        }

        return $this->weeklyPhotoMapper->getPhotosOfTheWeek();
    }

    public function getCurrentPhotoOfTheWeek()
    {
        return $this->weeklyPhotoMapper->getCurrentPhotoOfTheWeek();
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

        $this->photoMapper->flush();
    }

    /**
     * Count a vote for the specified photo.
     *
     * @param int $photoId
     */
    public function countVote($photoId)
    {
        $member = $this->userRole->getMember();
        if (null !== $this->voteMapper->findVote($photoId, $member->getLidnr())) {
            // Already voted
            return;
        }
        $photo = $this->getPhoto($photoId);
        $vote = new VoteModel($photo, $member);
        $this->voteMapper->persist($vote);
        $this->voteMapper->flush();
    }

    /**
     * Tags a user in the specified photo.
     *
     * @param int $photoId
     * @param int $lidnr
     *
     * @return TagModel|null
     */
    public function addTag($photoId, $lidnr)
    {
        if (!$this->isAllowed('add', 'tag')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to add tags.'));
        }

        if (is_null($this->findTag($photoId, $lidnr))) {
            $photo = $this->getPhoto($photoId);
            $member = $this->memberService->findMemberByLidnr($lidnr);
            $tag = new TagModel();
            $tag->setMember($member);
            $photo->addTag($tag);

            $this->photoMapper->flush();

            return $tag;
        } else {
            // Tag exists
            return null;
        }
    }

    /**
     * Retrieves a tag if it exists.
     *
     * @param int $photoId
     * @param int $lidnr
     *
     * @return TagModel|null
     */
    public function findTag($photoId, $lidnr)
    {
        if (!$this->isAllowed('view', 'tag')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to view tags.'));
        }

        return $this->tagMapper->findTag($photoId, $lidnr);
    }

    /**
     * Checks whether a user is tagged in a photo.
     *
     * @param int $photoId
     * @param int $lidnr
     *
     * @return bool
     */
    public function isTaggedIn($photoId, $lidnr)
    {
        $tag = $this->findTag($photoId, $lidnr);
        if (null != $tag) {
            return true;
        }

        return false;
    }

    /**
     * Removes a tag.
     *
     * @param int $photoId
     * @param int $lidnr
     *
     * @return bool indicating whether removing the tag succeeded
     */
    public function removeTag($photoId, $lidnr)
    {
        if (!$this->isAllowed('remove', 'tag')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to remove tags.'));
        }

        $tag = $this->findTag($photoId, $lidnr);
        if (!is_null($tag)) {
            $this->tagMapper->remove($tag);
            $this->tagMapper->flush();

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
            throw new NotAllowedException($this->translator->translate('Not allowed to view tags.'));
        }

        return $this->tagMapper->getTagsByLidnr($member->getLidnr());
    }

    /**
     * Gets the base directory from which the photo paths should be requested.
     *
     * @return string
     */
    public function getBaseDirectory()
    {
        return str_replace('public', '', $this->photoConfig['upload_dir']);
    }

    /**
     * Get the Acl.
     *
     * @return Acl
     */
    public function getAcl()
    {
        return $this->acl;
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
