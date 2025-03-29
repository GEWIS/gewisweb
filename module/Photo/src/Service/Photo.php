<?php

declare(strict_types=1);

namespace Photo\Service;

use Application\Service\FileStorage as FileStorageService;
use DateInterval;
use DateTime;
use Decision\Model\Member as MemberModel;
use Decision\Service\Member as MemberService;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Exception;
use Laminas\Http\Response\Stream;
use Laminas\Mvc\I18n\Translator;
use Photo\Mapper\Photo as PhotoMapper;
use Photo\Mapper\ProfilePhoto as ProfilePhotoMapper;
use Photo\Mapper\Tag as TagMapper;
use Photo\Mapper\Vote as VoteMapper;
use Photo\Mapper\WeeklyPhoto as WeeklyPhotoMapper;
use Photo\Model\Album as AlbumModel;
use Photo\Model\HiddenPhoto as HiddenPhotoModel;
use Photo\Model\Photo as PhotoModel;
use Photo\Model\ProfilePhoto as ProfilePhotoModel;
use Photo\Model\Tag as TagModel;
use Photo\Model\Vote as VoteModel;
use Photo\Model\WeeklyPhoto as WeeklyPhotoModel;
use User\Permissions\NotAllowedException;

use function count;
use function preg_replace;
use function str_replace;
use function strpos;
use function substr;

/**
 * Photo service.
 */
class Photo
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly MemberService $memberService,
        private readonly FileStorageService $storageService,
        private readonly PhotoMapper $photoMapper,
        private readonly TagMapper $tagMapper,
        private readonly VoteMapper $voteMapper,
        private readonly WeeklyPhotoMapper $weeklyPhotoMapper,
        private readonly ProfilePhotoMapper $profilePhotoMapper,
        private readonly array $photoConfig,
    ) {
    }

    /**
     * Get all photos in an album.
     *
     * @param AlbumModel $album      the album to get the photos from
     * @param int        $start      the result to start at
     * @param int|null   $maxResults max amount of results to return, null for infinite
     *
     * @return PhotoModel[]
     */
    public function getPhotos(
        AlbumModel $album,
        int $start = 0,
        ?int $maxResults = null,
    ): array {
        if (!$this->aclService->isAllowed('view', 'photo')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view photos'));
        }

        return $this->photoMapper->getAlbumPhotos(
            $album,
            $start,
            $maxResults,
        );
    }

    /**
     * Returns a Laminas response to be used for downloading a photo.
     */
    public function getPhotoDownload(int $photoId): ?Stream
    {
        $photo = $this->getPhoto($photoId);

        if (null === $photo) {
            return null;
        }

        if (!$this->aclService->isAllowed('download', $photo)) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to download photos'));
        }

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
            throw new NotAllowedException($this->translator->translate('You are not allowed to view photos'));
        }

        return $this->photoMapper->find($id);
    }

    /**
     * Returns a unique file name for a photo.
     *
     * @param PhotoModel $photo the photo to get a name for
     */
    public function getPhotoFileName(PhotoModel $photo): string
    {
        // filtering is required to prevent invalid characters in file names.
        $albumName = preg_replace('/[^\p{L}\p{N}\s]/u', '', $photo->getAlbum()->getName());

        // don't put spaces in file names
        $albumName = str_replace(' ', '-', $albumName);
        $extension = substr($photo->getPath(), strpos($photo->getPath(), '.'));

        return $albumName . '-' . $photo->getDateTime()->format('Y') . '-' . $photo->getId() . $extension;
    }

    /**
     * Removes a photo from the database and deletes its files, including thumbs
     * from the server.
     *
     * @param int $photoId the id of the photo to delete
     *
     * @return bool indicating whether the deletion was successful
     *
     * @throws ORMException
     */
    public function deletePhoto(int $photoId): bool
    {
        $photo = $this->getPhoto($photoId);
        if (null === $photo) {
            return false;
        }

        $this->photoMapper->remove($photo);
        $this->photoMapper->flush();

        return true;
    }

    /**
     * Deletes all files associated with a photo.
     */
    public function deletePhotoFiles(PhotoModel $photo): void
    {
        if (1 !== $this->photoMapper->count(['path' => $photo->getPath()])) {
            return;
        }

        $this->deletePhotoFile($photo->getPath());
    }

    /**
     * Deletes a stored photo at a given path.
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
     * @throws ORMException
     * @throws Exception
     */
    public function generatePhotoOfTheWeek(
        ?DateTime $beginDate = null,
        ?DateTime $endDate = null,
    ): ?WeeklyPhotoModel {
        if (
            null === $beginDate
            || null === $endDate
        ) {
            $beginDate = (new DateTime())->sub(new DateInterval('P1W'));
            $endDate = new DateTime();
        }

        $bestPhoto = $this->determinePhotoOfTheWeek($beginDate, $endDate);
        if (null === $bestPhoto) {
            return null;
        }

        $weeklyPhoto = new WeeklyPhotoModel();
        $weeklyPhoto->setPhoto($bestPhoto);
        $weeklyPhoto->setWeek($beginDate);
        $mapper = $this->weeklyPhotoMapper;
        $mapper->persist($weeklyPhoto);
        $mapper->flush();

        return $weeklyPhoto;
    }

    /**
     * Determine which photo is the photo of the week.
     *
     * @throws Exception
     */
    public function determinePhotoOfTheWeek(
        DateTime $beginDate,
        DateTime $endDate,
    ): ?PhotoModel {
        $results = $this->voteMapper->getVotesInRange($beginDate, $endDate);

        if (empty($results)) {
            return null;
        }

        $bestRating = -1;
        $bestPhoto = null;

        foreach ($results as $res) {
            $photo = $this->photoMapper->find($res[1]);
            $rating = $this->ratePhoto($photo, $res[2]);

            if (
                $this->weeklyPhotoMapper->hasBeenPhotoOfTheWeek($photo)
                || $rating <= $bestRating
            ) {
                continue;
            }

            $bestPhoto = $photo;
            $bestRating = $rating;
        }

        return $bestPhoto;
    }

    /**
     * Determine which photo is best suited as profile picture.
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
     * @throws Exception
     */
    public function removeProfilePhoto(?ProfilePhotoModel $profilePhoto = null): void
    {
        if (null === $profilePhoto) {
            $profilePhoto = $this->getStoredProfilePhoto(
                $this->aclService->getUserIdentityOrThrowException()->getLidnr(),
            );
        }

        if (null === $profilePhoto) {
            return;
        }

        $mapper = $this->profilePhotoMapper;
        $mapper->remove($profilePhoto);
        $mapper->flush();
    }

    /**
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

            if ($rating <= $bestRating) {
                continue;
            }

            $bestPhoto = $photo;
            $bestRating = $rating;
        }

        $this->cacheProfilePhoto($lidnr, $bestPhoto);

        return $bestPhoto;
    }

    /**
     * @throws ORMException
     */
    private function cacheProfilePhoto(
        int $lidnr,
        PhotoModel $photo,
    ): void {
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
     * @throws ORMException
     */
    private function storeProfilePhoto(
        PhotoModel $photo,
        MemberModel $member,
        DateTime $dateTime,
        bool $explicit = false,
    ): void {
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
     * @throws ORMException
     */
    public function setProfilePhoto(int $photoId): void
    {
        $photo = $this->getPhoto($photoId);
        if (null === $photo) {
            throw new NoResultException();
        }

        $member = $this->aclService->getUserIdentityOrThrowException()->getMember();
        $lidnr = $member->getLidnr();
        $profilePhoto = $this->getStoredProfilePhoto($lidnr);

        if (null !== $profilePhoto) {
            $this->removeProfilePhoto($profilePhoto);
        }

        $dateTime = (new DateTime())->add(new DateInterval('P1Y'));
        $this->storeProfilePhoto($photo, $member, $dateTime, true);
    }

    /**
     * @throws Exception
     */
    public function hasExplicitProfilePhoto(int $lidnr): bool
    {
        $profilePhoto = $this->getStoredProfilePhoto($lidnr);
        if (null !== $profilePhoto) {
            return $profilePhoto->isExplicit();
        }

        return false;
    }

    /**
     * Determine the preference rating of the photo.
     */
    public function ratePhoto(
        PhotoModel $photo,
        int $occurences,
    ): float|int {
        $tagged = count($photo->getTags()) > 0;
        $now = new DateTime();
        $age = $now->diff($photo->getDateTime(), true)->days;
        $res = $occurences * (1 + 1 / $age);

        return $tagged ? 1.5 * $res : $res;
    }

    /**
     * Determine the preference rating of the photo.
     */
    public function ratePhotoForMember(PhotoModel $photo): float|int
    {
        $now = new DateTime();
        $age = $now->diff($photo->getDateTime(), true)->days;

        $votes = $photo->getVoteCount();
        $tags = $photo->getTagCount();

        $baseRating = $votes / $tags ** 1.25;
        // Prevent division by zero.
        if ($age < 14) {
            return $baseRating * (14 - $age);
        }

        return $baseRating / $age;
    }

    public function getCurrentPhotoOfTheWeek(): ?WeeklyPhotoModel
    {
        return $this->weeklyPhotoMapper->getCurrentPhotoOfTheWeek();
    }

    /**
     * Count a vote for the specified photo.
     *
     * @throws ORMException
     */
    public function countVote(int $photoId): void
    {
        $identity = $this->aclService->getUserIdentityOrThrowException();

        if (null !== $this->voteMapper->findVote($photoId, $identity->getLidnr())) {
            // Already voted
            return;
        }

        $photo = $this->getPhoto($photoId);
        $vote = new VoteModel($photo, $identity->getMember());

        $this->voteMapper->persist($vote);
        $this->voteMapper->flush();
    }

    /**
     * Tags a user in the specified photo.
     *
     * @throws ORMException
     */
    public function addTag(
        int $photoId,
        int $lidnr,
    ): ?TagModel {
        if (null === $this->findTag($photoId, $lidnr)) {
            $photo = $this->getPhoto($photoId);
            $member = $this->memberService->findMemberByLidnr($lidnr);

            if (
                null === $member
                || $member->isExpired()
            ) {
                return null;
            }

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
     */
    public function findTag(
        int $photoId,
        int $lidnr,
    ): ?TagModel {
        if (!$this->aclService->isAllowed('view', 'tag')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view tags'));
        }

        return $this->tagMapper->findTag($photoId, $lidnr);
    }

    /**
     * Checks whether a user is tagged in a photo.
     */
    public function isTaggedIn(
        int $photoId,
        int $lidnr,
    ): bool {
        $tag = $this->findTag($photoId, $lidnr);

        return null !== $tag;
    }

    /**
     * Removes a tag.
     *
     * @return bool indicating whether removing the tag succeeded
     *
     * @throws ORMException
     */
    public function removeTag(
        int $photoId,
        int $lidnr,
    ): bool {
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
     * @return TagModel[]
     */
    public function getTagsForMember(MemberModel $member): array
    {
        if (!$this->aclService->isAllowed('view', 'tag')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view tags'));
        }

        return $this->tagMapper->getTagsByLidnr($member->getLidnr());
    }

    /**
     * Hide a photo from the profile page of the logged-in member.
     *
     * @throws ORMException
     */
    public function addHiddenPhoto(int $photoId): ?HiddenPhotoModel
    {
        $lidnr = $this->aclService->getUserIdentityOrThrowException()->getLidnr();

        if (null === $this->findHiddenPhoto($photoId, $lidnr)) {
            $photo = $this->getPhoto($photoId);
            $member = $this->memberService->findMemberByLidnr($lidnr);

            if (
                null === $member
                || $member->isExpired()
            ) {
                return null;
            }

            $hiddenPhoto = new HiddenPhotoModel();
            $hiddenPhoto->setMember($member);
            $photo->addHiddenPhoto($hiddenPhoto);

            $this->photoMapper->flush();

            return $hiddenPhoto;
        }

        return null;
    }

    /**
     * Removes a hiddenPhoto.
     *
     * @return bool indicating whether removing the hiddenPhoto succeeded
     *
     * @throws ORMException
     */
    public function removeHiddenPhoto(
        int $photoId,
        int $lidnr,
    ): bool {
        $hiddenPhoto = $this->findHiddenPhoto($photoId, $lidnr);

        if (null !== $hiddenPhoto) {
            $this->hiddenPhotoMapper->remove($hiddenPhoto);
            $this->hiddenPhotoMapper->flush();

            return true;
        }

        return false;
    }

    /**
     * Gets the base directory from which the photo paths should be requested.
     */
    public function getBaseDirectory(): string
    {
        return str_replace('public', '', (string) $this->photoConfig['upload_dir']);
    }

    /**
     * Checks if the currently logged-in user has recently voted for a photo.
     */
    public function hasRecentVote(): bool
    {
        $this->aclService->isAllowed('view', 'album');

        $lidnr = $this->aclService->getUserIdentityOrThrowException()->getLidnr();

        return $this->voteMapper->hasRecentVote($lidnr);
    }

    /**
     * Hide a Photo of the Week.
     */
    public function hidePhotoOfTheWeek(WeeklyPhotoModel $potw): void
    {
        $potw->setHidden(true);

        $this->weeklyPhotoMapper->persist($potw);
        $this->weeklyPhotoMapper->flush();
    }
}
