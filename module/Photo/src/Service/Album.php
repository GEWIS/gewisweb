<?php

declare(strict_types=1);

namespace Photo\Service;

use Application\Service\FileStorage as FileStorageService;
use DateInterval;
use DateTime;
use Decision\Model\AssociationYear;
use Decision\Service\Member as MemberService;
use Exception;
use Laminas\Mvc\I18n\Translator;
use Photo\Form\Album as AlbumForm;
use Photo\Form\SearchAlbum as SearchAlbumForm;
use Photo\Mapper\Album as AlbumMapper;
use Photo\Mapper\Tag as TagMapper;
use Photo\Mapper\WeeklyPhoto as WeeklyPhotoMapper;
use Photo\Model\Album as AlbumModel;
use Photo\Model\MemberAlbum as MemberAlbumModel;
use Photo\Model\WeeklyAlbum as WeeklyAlbumModel;
use Photo\Model\WeeklyPhoto as WeeklyPhotoModel;
use Photo\Service\AlbumCover as AlbumCoverService;
use Photo\Service\Photo as PhotoService;
use User\Permissions\NotAllowedException;

use function boolval;
use function range;
use function sprintf;

/**
 * Album service.
 */
class Album
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly PhotoService $photoService,
        private readonly AlbumCoverService $albumCoverService,
        private readonly MemberService $memberService,
        private readonly FileStorageService $storageService,
        private readonly AlbumMapper $albumMapper,
        private readonly TagMapper $tagMapper,
        private readonly WeeklyPhotoMapper $weeklyPhotoMapper,
        private readonly AlbumForm $albumForm,
        private readonly SearchAlbumForm $searchAlbumForm,
    ) {
    }

    /**
     * Returns all albums for a given association year.
     * In this context an association year is defined as the year which contains
     * the first day of the association year.
     *
     * Example: A value of 2010 would represent the association year 2010/2011
     *
     * @param int  $year          the year in which the albums have been created
     * @param bool $onlyPublished whether to only retrieve published albums or all albums
     *
     * @return AlbumModel[]
     */
    public function getAlbumsByYear(
        int $year,
        bool $onlyPublished = true,
    ): array {
        if (!$this->aclService->isAllowed('view', 'album')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view albums'));
        }

        if (!$onlyPublished && !$this->aclService->isAllowed('unpublished', 'album')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view unpublished albums'),
            );
        }

        $start = DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $year . '-' . AssociationYear::ASSOCIATION_YEAR_START_MONTH . '-'
            . AssociationYear::ASSOCIATION_YEAR_START_DAY . ' 0:00:00',
        );
        $end = clone $start;
        $end->add(new DateInterval('P1Y'));

        return $this->albumMapper->getAlbumsInDateRange($start, $end, $onlyPublished);
    }

    /**
     * Retrieves all root albums which do not have a startDateTime specified.
     * This is in most cases analogous to returning all empty albums.
     *
     * @return AlbumModel[]
     */
    public function getAlbumsWithoutDate(): array
    {
        if (!$this->aclService->isAllowed('nodate', 'album')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to view albums without dates'),
            );
        }

        return $this->albumMapper->getAlbumsWithoutDate();
    }

    /**
     * Gets a list of all association years of which photos are available.
     * In this context an association year is defined as the year which contains
     * the first day of the association year.
     *
     * Example: A value of 2010 would represent the association year 2010/2011
     *
     * @return int[] representing years
     */
    public function getAlbumYears(bool $onlyPublished = true): array
    {
        $oldest = $this->albumMapper->getOldestAlbum($onlyPublished);
        $newest = $this->albumMapper->getNewestAlbum($onlyPublished);

        if (
            null === $oldest
            || null === $newest
            || null === $oldest->getStartDateTime()
            || null === $newest->getEndDateTime()
        ) {
            return [];
        }

        $startYear = AssociationYear::fromDate($oldest->getStartDateTime())->getYear();
        $endYear = AssociationYear::fromDate($newest->getEndDateTime())->getYear();

        // We make the reasonable assumption that at least one photo is taken every year
        return range($startYear, $endYear);
    }

    /**
     * Creates a new album.
     *
     * @param int|null $parentId the id of the parent album
     * @param array    $data     The post data to use for the album
     *
     * @throws Exception
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function createAlbum(
        ?int $parentId,
        array $data,
    ): AlbumModel {
        if (!$this->aclService->isAllowed('create', 'album')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to create albums'));
        }

        $album = new AlbumModel();
        $album->setName($data['name']);
        $album->setPublished(boolval($data['published']));

        if (null !== $parentId) {
            $album->setParent($this->getAlbum($parentId));
        }

        $this->albumMapper->persist($album);
        $this->albumMapper->flush();

        return $album;
    }

    /**
     * Gets an album using the album id.
     *
     * @param int    $albumId the id of the album
     * @param string $type    "album"|"member"|"weekly"
     *
     * @return MemberAlbumModel|AlbumModel|WeeklyAlbumModel|null album matching the given id
     *
     * @throws Exception If there are no sufficient permissions.
     */
    public function getAlbum(
        int $albumId,
        string $type = 'album',
    ): MemberAlbumModel|AlbumModel|WeeklyAlbumModel|null {
        if (!$this->aclService->isAllowed('view', 'album')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view albums'));
        }

        $album = match ($type) {
            'album' => $this->albumMapper->find($albumId),
            'member' => $this->getMemberAlbum($albumId),
            'weekly' => $this->getWeeklyAlbum($albumId),
            default => throw new Exception('Album type not allowed'),
        };

        if (
            null !== $album
            && !$this->aclService->isAllowed('view', $album)
        ) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view albums'));
        }

        return $album;
    }

    private function getMemberAlbum(int $lidNr): ?MemberAlbumModel
    {
        $member = $this->memberService->findMemberByLidnr($lidNr);

        if (null === $member) {
            return null;
        }

        $album = new MemberAlbumModel($lidNr, $member);
        $album->setName($member->getFullName());
        $album->setPublished(true);
        $album->setStartDateTime($member->getBirth()); // ugly fix
        $album->setEndDateTime(new DateTime());
        $album->addPhotos($this->photoService->getPhotos($album));

        return $album;
    }

    /**
     * Retrieves all WeeklyPhotos.
     *
     * @return WeeklyPhotoModel[]
     */
    public function getLastPhotosOfTheWeekPerYear(): array
    {
        $oldest = $this->weeklyPhotoMapper->getOldestPhotoOfTheWeek();
        $newest = $this->weeklyPhotoMapper->getNewestPhotoOfTheWeek();

        if (
            null === $oldest
            || null === $newest
        ) {
            return [];
        }

        $startYear = AssociationYear::fromDate($oldest->getWeek())->getYear();
        $endYear = AssociationYear::fromDate($newest->getWeek())->getYear();
        $years = range($startYear, $endYear);

        $photos = [];
        foreach ($years as $year) {
            /** @var WeeklyPhotoModel|null $photo */
            $photo = $this->weeklyPhotoMapper->getPhotosOfTheWeekInYear($year, true);

            if (null === $photo) {
                continue;
            }

            $photos[$year] = $photo;
        }

        return $photos;
    }

    private function getWeeklyAlbum(int $year): ?WeeklyAlbumModel
    {
        /** @var WeeklyPhotoModel[] $photos */
        $photos = $this->weeklyPhotoMapper->getPhotosOfTheWeekInYear($year);

        if (empty($photos)) {
            return null;
        }

        $dates = [];
        $actualPhotos = [];

        foreach ($photos as $photo) {
            $actualPhotos[] = $photo->getPhoto();
            $dates[] = $photo->getWeek();
        }

        $weeklyAlbum = new WeeklyAlbumModel($year, $dates);
        $weeklyAlbum->setName(
            sprintf(
                $this->translator->translate('Photos of the Week in %d/%d'),
                $year,
                $year + 1,
            ),
        );
        $weeklyAlbum->setPublished(true);

        $startDate = DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $year . '-' . AssociationYear::ASSOCIATION_YEAR_START_MONTH . '-'
            . AssociationYear::ASSOCIATION_YEAR_START_DAY . ' 00:00:00',
        );
        $endDate = clone $startDate;
        $endDate->add(new DateInterval('P1Y'));

        $weeklyAlbum->setStartDateTime($startDate);
        $weeklyAlbum->setEndDateTime($endDate);
        $weeklyAlbum->addPhotos($actualPhotos);

        return $weeklyAlbum;
    }

    /**
     * Updates the metadata of an album using post data.
     *
     * @return bool indicating if the update was successful
     *
     * @throws Exception
     */
    public function updateAlbum(): bool
    {
        if (!$this->aclService->isAllowed('edit', 'album')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit albums'));
        }

        $this->albumMapper->flush();

        return true;
    }

    /**
     * Retrieves the form for editing the specified album.
     *
     * @param int|null $albumId of the album
     *
     * @throws Exception
     */
    public function getAlbumForm(?int $albumId = null): AlbumForm
    {
        if (!$this->aclService->isAllowed('edit', 'album')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit albums'));
        }

        $form = $this->albumForm;

        if (null !== $albumId) {
            $album = $this->getAlbum($albumId);
            $form->bind($album);
        }

        return $form;
    }

    /**
     * Moves an album to new parent album.
     *
     * @param int      $albumId  the id of the album to be moved
     * @param int|null $parentId the id of the new parent or null if the album should not be a subalbum
     *
     * @return bool indicating if the move was successful
     *
     * @throws Exception
     */
    public function moveAlbum(
        int $albumId,
        ?int $parentId,
    ): bool {
        if (!$this->aclService->isAllowed('move', 'album')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to move albums'));
        }

        $album = $this->getAlbum($albumId);

        if (
            null === $album
            || $albumId === $parentId
        ) {
            return false;
        }

        $parent = null === $parentId ? null : $this->getAlbum($parentId);

        // If the current album is already a subalbum, remove it from it's parent's children.
        $album->getParent()?->removeAlbum($album);

        // Set the new parent.
        $album->setParent($parent);
        $this->albumMapper->flush();

        return true;
    }

    /**
     * Removes an album and all subalbums recursively, including all photos.
     *
     * @param int $albumId the id of the album to remove
     *
     * @throws Exception
     */
    public function deleteAlbum(int $albumId): void
    {
        if (!$this->aclService->isAllowed('delete', 'album')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to delete albums'));
        }

        $album = $this->getAlbum($albumId);

        if (null === $album) {
            return;
        }

        $this->albumMapper->remove($album);
        $this->albumMapper->flush();
    }

    /**
     * Updates the given album with a newly generated cover photo.
     *
     * @throws Exception
     */
    public function generateAlbumCover(int $albumId): ?string
    {
        if (!$this->aclService->isAllowed('edit', 'album')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to generate album covers'));
        }

        $album = $this->getAlbum($albumId);
        //if an existing cover photo was generated earlier, delete it.
        $coverPath = $this->albumCoverService->createCover($album);

        if (null === $coverPath) {
            return null;
        }

        if (null !== $album->getCoverPath()) {
            $this->storageService->removeFile($album->getCoverPath());
        }

        $album->setCoverPath($coverPath);
        $this->albumMapper->flush();

        return $coverPath;
    }

    /**
     * Deletes the file belonging to the album cover for an album.
     */
    public function deleteAlbumCover(AlbumModel $album): void
    {
        if (null === ($albumCover = $album->getCoverPath())) {
            return;
        }

        $this->photoService->deletePhotoFile($albumCover);
    }

    /**
     * Moves a photo to a new album.
     *
     * @param int $photoId the id of the photo
     * @param int $albumId the id of the new album
     *
     * @return bool indicating whether move was successful
     *
     * @throws Exception
     */
    public function movePhoto(
        int $photoId,
        int $albumId,
    ): bool {
        $photo = $this->photoService->getPhoto($photoId);
        $album = $this->getAlbum($albumId);

        if (
            null === $photo
            || null === $album
        ) {
            return false;
        }

        $photo->setAlbum($album);
        $this->albumMapper->flush();

        return true;
    }

    /**
     * Get all unique albums a certain member is tagged in
     *
     * @return array<array-key, array{album_id: int}>
     */
    public function getAlbumsByMember(int $lidnr): array
    {
        return $this->tagMapper->getAlbumsByMember($lidnr);
    }

    public function getSearchAlbumForm(): SearchAlbumForm
    {
        return $this->searchAlbumForm;
    }

    /**
     * Search for albums.
     *
     * @param array $data Search data
     *
     * @return AlbumModel[]
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function search(array $data): array
    {
        return $this->albumMapper->search($data['query']);
    }
}
