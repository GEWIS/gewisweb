<?php

namespace Photo\Service;

use Application\Service\FileStorage as FileStorageService;
use DateInterval;
use DateTime;
use Decision\Model\Member as MemberModel;
use Decision\Service\Member as MemberService;
use Doctrine\ORM\ORMException;
use Exception;
use Laminas\Http\Response\Stream;
use Laminas\I18n\Filter\Alnum;
use Laminas\Mvc\I18n\Translator;
use Photo\Mapper\{
    PHoto as PhotoMapper,
    ProfilePhoto as ProfilePhotoMapper,
    Tag as TagMapper,
    Vote as VoteMapper,
    WeeklyPhoto as WeeklyPhotoMapper,
};
use Photo\Model\{
    Album as AlbumModel,
    Photo as PhotoModel,
    ProfilePhoto as ProfilePhotoModel,
    Tag as TagModel,
    Vote as VoteModel,
    WeeklyPhoto as WeeklyPhotoModel,
};
use User\Permissions\NotAllowedException;

/**
 * Photo service.
 */
class Photo
{
    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * @var MemberService
     */
    private MemberService $memberService;

    /**
     * @var FileStorageService
     */
    private FileStorageService $storageService;

    /**
     * @var PhotoMapper
     */
    private PhotoMapper $photoMapper;

    /**
     * @var TagMapper
     */
    private TagMapper $tagMapper;

    /**
     * @var VoteMapper
     */
    private VoteMapper $voteMapper;

    /**
     * @var WeeklyPhotoMapper
     */
    private WeeklyPhotoMapper $weeklyPhotoMapper;

    /**
     * @var ProfilePhotoMapper
     */
    private ProfilePhotoMapper $profilePhotoMapper;

    /**
     * @var array
     */
    private array $photoConfig;

    /**
     * @var AclService
     */
    private AclService $aclService;

    public function __construct(
        Translator $translator,
        MemberService $memberService,
        FileStorageService $storageService,
        PhotoMapper $photoMapper,
        TagMapper $tagMapper,
        VoteMapper $voteMapper,
        WeeklyPhotoMapper $weeklyPhotoMapper,
        ProfilePhotoMapper $profilePhotoMapper,
        array $photoConfig,
        AclService $aclService
    ) {
        $this->translator = $translator;
        $this->memberService = $memberService;
        $this->storageService = $storageService;
        $this->photoMapper = $photoMapper;
        $this->tagMapper = $tagMapper;
        $this->voteMapper = $voteMapper;
        $this->weeklyPhotoMapper = $weeklyPhotoMapper;
        $this->profilePhotoMapper = $profilePhotoMapper;
        $this->photoConfig = $photoConfig;
        $this->aclService = $aclService;
    }

    /**
     * Get all photos in an album.
     *
     * @param AlbumModel $album the album to get the photos from
     * @param int $start the result to start at
     * @param int|null $maxResults max amount of results to return, null for infinite
     *
     * @return array of AlbumModel
     */
    public function getPhotos(AlbumModel $album, int $start = 0, ?int $maxResults = null): array
    {
        if (!$this->aclService->isAllowed('view', 'photo')) {
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
     * @return Stream|null
     */
    public function getPhotoDownload(int $photoId): ?Stream
    {
        if (!$this->aclService->isAllowed('download', 'photo')) {
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
     * @return PhotoModel|null photo matching the given id
     */
    public function getPhoto(int $id): ?PhotoModel
    {
        if (!$this->aclService->isAllowed('view', 'photo')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to view photos'));
        }

        return $this->photoMapper->find($id);
    }

    /**
     * Returns a unique file name for a photo.
     *
     * @param PhotoModel $photo the photo to get a name for
     *
     * @return string
     */
    public function getPhotoFileName(PhotoModel $photo): string
    {
        // filtering is required to prevent invalid characters in file names.
        $filter = new Alnum(true);
        $albumName = $filter->filter($photo->getAlbum()->getName());

        // don't put spaces in file names
        $albumName = str_replace(' ', '-', $albumName);
        $extension = substr($photo->getPath(), strpos($photo->getPath(), '.'));

        return $albumName . '-' . $photo->getDateTime()->format('Y') . '-' . $photo->getId() . $extension;
    }

    /**
     * Get the photo data belonging to a certain photo.
     *
     * @param int $photoId the id of the photo to retrieve
     * @param AlbumModel|null $album
     *
     * @return array|null of data about the photo, which is useful inside a view
     *                    or null if the photo was not found
     *
     * @throws Exception
     */
    public function getPhotoData(int $photoId, ?AlbumModel $album = null): ?array
    {
        if (!$this->aclService->isAllowed('view', 'photo')) {
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

        $lidnr = $this->aclService->getIdentity()->getLidnr();
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
     * @return PhotoModel|null the next photo
     */
    public function getNextPhoto(
        PhotoModel $photo,
        AlbumModel $album
    ): ?PhotoModel
    {
        if (!$this->aclService->isAllowed('view', 'photo')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to view photos'));
        }

        return $this->photoMapper->getNextPhoto($photo, $album);
    }

    /**
     * Returns the previous photo in the album to display.
     *
     * @return PhotoModel|null the next photo
     */
    public function getPreviousPhoto(
        PhotoModel $photo,
        AlbumModel $album
    ): ?PhotoModel
    {
        if (!$this->aclService->isAllowed('view', 'photo')) {
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
     * @throws ORMException
     */
    public function deletePhoto(int $photoId): bool
    {
        if (!$this->aclService->isAllowed('delete', 'photo')) {
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
    public function deletePhotoFiles(PhotoModel $photo): void
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
    public function deletePhotoFile(string $path): bool
    {
        return $this->storageService->removeFile($path);
    }

    /**
     * Generates the PhotoOfTheWeek and adds it to the list
     * if at least one photo has been viewed in the specified time.
     * The parameters determine the week to check the photos of.
     *
     * @param DateTime|null $begindate
     * @param DateTime|null $enddate
     *
     * @return WeeklyPhotoModel|null
     * @throws ORMException
     * @throws Exception
     */
    public function generatePhotoOfTheWeek(?DateTime $begindate = null, ?DateTime $enddate = null): ?WeeklyPhotoModel
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
     * @throws Exception
     */
    public function determinePhotoOfTheWeek(DateTime $begindate, DateTime $enddate): ?PhotoModel
    {
        $results = $this->voteMapper->getVotesInRange($begindate, $enddate);

        if (empty($results)) {
            return null;
        }

        $bestRating = -1;
        $bestPhoto = null;

        foreach ($results as $res) {
            $photo = $this->photoMapper->find($res[1]);
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
    public function getProfilePhoto(int $lidnr): ?PhotoModel
    {
        $profilePhoto = $this->getStoredProfilePhoto($lidnr);

        if (null !== $profilePhoto) {
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
    private function getStoredProfilePhoto(int $lidnr): ?ProfilePhotoModel
    {
        $profilePhoto = $this->profilePhotoMapper->getProfilePhotoByLidnr($lidnr);

        if (null !== $profilePhoto) {
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
     * @param ProfilePhotoModel|null $profilePhoto
     *
     * @throws Exception
     */
    public function removeProfilePhoto(ProfilePhotoModel $profilePhoto = null): void
    {
        if (null === $profilePhoto) {
            $member = $this->aclService->getIdentity()->getMember();
            $lidnr = $member->getLidnr();
            $profilePhoto = $this->getStoredProfilePhoto($lidnr);
        }

        if (null !== $profilePhoto) {
            $mapper = $this->profilePhotoMapper;
            $mapper->remove($profilePhoto);
            $mapper->flush();
        }
    }

    /**
     * @param int $lidnr
     *
     * @return PhotoModel|null
     * @throws Exception
     */
    private function determineProfilePhoto(int $lidnr): ?PhotoModel
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
     * @param PhotoModel $photo
     *
     * @throws ORMException
     */
    private function cacheProfilePhoto(int $lidnr, PhotoModel $photo): void
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
     * @param PhotoModel $photo
     * @param MemberModel $member
     * @param DateTime $dateTime
     * @param bool $explicit
     *
     * @throws ORMException
     */
    private function storeProfilePhoto(
        PhotoModel $photo,
        MemberModel $member,
        DateTime $dateTime,
        bool $explicit = false,
    ): void
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
    public function setProfilePhoto(int $photoId): void
    {
        $photo = $this->getPhoto($photoId);
        $member = $this->aclService->getIdentity()->getMember();
        $lidnr = $member->getLidnr();
        $profilePhoto = $this->getStoredProfilePhoto($lidnr);

        if (null !== $profilePhoto) {
            $this->removeProfilePhoto($profilePhoto);
        }

        $dateTime = (new DateTime())->add(new DateInterval('P1Y'));
        $this->storeProfilePhoto($photo, $member, $dateTime, true);
    }

    /**
     * @param int $lidnr
     *
     * @return bool
     * @throws Exception
     */
    public function hasExplicitProfilePhoto(int $lidnr): bool
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
     * @return float|int
     */
    public function ratePhoto(PhotoModel $photo, int $occurences): float|int
    {
        $tagged = count($photo->getTags()) > 0;
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
     * @return float|int
     */
    public function ratePhotoForMember(PhotoModel $photo): float|int
    {
        $now = new DateTime();
        $age = $now->diff($photo->getDateTime(), true)->days;

        $votes = $photo->getVoteCount();
        $tags = $photo->getTagCount();

        $baseRating = $votes / pow($tags, 1.25);
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
    public function getPhotosOfTheWeek(): array
    {
        if (!$this->aclService->isAllowed('view', 'photo')) {
            throw new NotAllowedException(
                $this->translator->translate('Not allowed to view previous photos of the week')
            );
        }

        return $this->weeklyPhotoMapper->getPhotosOfTheWeek();
    }

    /**
     * @return WeeklyPhotoModel|null
     */
    public function getCurrentPhotoOfTheWeek(): ?WeeklyPhotoModel
    {
        return $this->weeklyPhotoMapper->getCurrentPhotoOfTheWeek();
    }

    /**
     * Count a vote for the specified photo.
     *
     * @param int $photoId
     *
     * @throws ORMException
     */
    public function countVote(int $photoId): void
    {
        $identity = $this->aclService->getIdentity();

        if (null !== $this->voteMapper->findVote($photoId, $identity->getLidnr())) {
            // Already voted
            return;
        }

        $photo = $this->getPhoto($photoId);
        $vote = new VoteModel($photo, $identity);

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
     * @throws ORMException
     */
    public function addTag(int $photoId, int $lidnr): ?TagModel
    {
        if (!$this->aclService->isAllowed('add', 'tag')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to add tags.'));
        }

        if (null === $this->findTag($photoId, $lidnr)) {
            $photo = $this->getPhoto($photoId);
            $member = $this->memberService->findMemberByLidnr($lidnr);
            $tag = new TagModel();
            $tag->setMember($member);
            $photo->addTag($tag);

            $this->photoMapper->flush();

            return $tag;
        }

        return null;
    }

    /**
     * Retrieves a tag if it exists.
     *
     * @param int $photoId
     * @param int $lidnr
     *
     * @return TagModel|null
     */
    public function findTag(int $photoId, int $lidnr): ?TagModel
    {
        if (!$this->aclService->isAllowed('view', 'tag')) {
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
    public function isTaggedIn(int $photoId, int $lidnr): bool
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
     * @throws ORMException
     */
    public function removeTag(int $photoId, int $lidnr): bool
    {
        if (!$this->aclService->isAllowed('remove', 'tag')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to remove tags.'));
        }

        $tag = $this->findTag($photoId, $lidnr);

        if (null !== $tag) {
            $this->tagMapper->remove($tag);
            $this->tagMapper->flush();

            return true;
        }

        return false;
    }

    /**
     * Gets all photos in which a member has been tagged.
     *
     * @param MemberModel $member
     *
     * @return array
     */
    public function getTagsForMember(MemberModel $member): array
    {
        if (!$this->aclService->isAllowed('view', 'tag')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to view tags.'));
        }

        return $this->tagMapper->getTagsByLidnr($member->getLidnr());
    }

    /**
     * Gets the base directory from which the photo paths should be requested.
     *
     * @return string
     */
    public function getBaseDirectory(): string
    {
        return str_replace('public', '', $this->photoConfig['upload_dir']);
    }
}
