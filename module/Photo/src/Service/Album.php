<?php

namespace Photo\Service;

use Application\Service\AbstractAclService;
use Application\Service\FileStorage;
use DateInterval;
use DateTime;
use Decision\Service\Member;
use Exception;
use Laminas\Mvc\I18n\Translator;
use Laminas\Permissions\Acl\Acl;
use Photo\Form\CreateAlbum;
use Photo\Form\EditAlbum;
use Photo\Model\Album as AlbumModel;
use Photo\Model\MemberAlbum;
use Photo\Model\VirtualAlbum;
use User\Model\User;
use User\Permissions\NotAllowedException;

/**
 * Album service.
 */
class Album extends AbstractAclService
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
     * @var Photo
     */
    private $photoService;

    /**
     * @var AlbumCover
     */
    private $albumCoverService;

    /**
     * @var Member
     */
    private $memberService;

    /**
     * @var FileStorage
     */
    private $storageService;

    /**
     * @var \Photo\Mapper\Album
     */
    private $albumMapper;

    /**
     * @var CreateAlbum
     */
    private $createAlbumForm;

    /**
     * @var EditAlbum
     */
    private $editAlbumForm;

    public function __construct(
        Translator $translator,
        $userRole,
        Acl $acl,
        Photo $photoService,
        AlbumCover $albumCoverService,
        Member $memberService,
        FileStorage $storageService,
        \Photo\Mapper\Album $albumMapper,
        CreateAlbum $createAlbumForm,
        EditAlbum $editAlbumForm
    )
    {
        $this->translator = $translator;
        $this->userRole = $userRole;
        $this->acl = $acl;
        $this->photoService = $photoService;
        $this->albumCoverService = $albumCoverService;
        $this->memberService = $memberService;
        $this->storageService = $storageService;
        $this->albumMapper = $albumMapper;
        $this->createAlbumForm = $createAlbumForm;
        $this->editAlbumForm = $editAlbumForm;
    }

    public function getRole()
    {
        return $this->userRole;
    }

    /**
     * A GEWIS association year starts 01-07.
     */
    public const ASSOCIATION_YEAR_START_MONTH = 7;
    public const ASSOCIATION_YEAR_START_DAY = 1;

    /**
     * Retrieves all the albums in the root directory or in the specified
     * album.
     *
     * @param int $start the result to start at
     * @param int $maxResults max amount of results to return,
     *                               null for infinite
     * @param AlbumModel $album The album to retrieve sub-albums
     *                               of
     *
     * @return array of albums
     */
    public function getAlbums($album = null, $start = 0, $maxResults = null)
    {
        if (!$this->isAllowed('view')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to view albums'));
        }
        if (null == $album) {
            return $this->albumMapper->getRootAlbums();
        } elseif ($album instanceof VirtualAlbum) {
            return [];
        } else {
            return $this->albumMapper->getSubAlbums(
                $album,
                $start,
                $maxResults
            );
        }
    }

    /**
     * Returns all albums for a given association year.
     * In this context an association year is defined as the year which contains
     * the first day of the association year.
     *
     * Example: A value of 2010 would represent the association year 2010/2011
     *
     * @param $year integer the year in which the albums have been created
     *
     * @return array of \Photo\Model\Albums
     */
    public function getAlbumsByYear($year)
    {
        if (!$this->isAllowed('view')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to view albums'));
        }
        if (!is_int($year)) {
            return [];
        }

        $start = DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $year . '-' . self::ASSOCIATION_YEAR_START_MONTH . '-'
            . self::ASSOCIATION_YEAR_START_DAY . ' 0:00:00'
        );
        $end = clone $start;
        $end->add(new DateInterval('P1Y'));

        return $this->albumMapper->getAlbumsInDateRange($start, $end);
    }

    /**
     * Retrieves all root albums which do not have a startDateTime specified.
     * This is in most cases analogous to returning all empty albums.
     *
     * @return array of \Photo\Model\Album
     */
    public function getAlbumsWithoutDate()
    {
        if (!$this->isAllowed('nodate')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to view albums without dates'));
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
     * @return array of integers representing years
     */
    public function getAlbumYears()
    {
        $oldest = $this->albumMapper->getOldestAlbum();
        $newest = $this->albumMapper->getNewestAlbum();
        if (
            is_null($oldest) || is_null($newest)
            || is_null($oldest->getStartDateTime())
            || is_null($newest->getEndDateTime())
        ) {
            return [null];
        }

        $startYear = $this->getAssociationYear($oldest->getStartDateTime());
        $endYear = $this->getAssociationYear($newest->getEndDateTime());

        // We make the reasonable assumption that at least one photo is taken every year
        return range($startYear, $endYear);
    }

    /**
     * Returns the association year to which a certain date belongs
     * In this context an association year is defined as the year which contains
     * the first day of the association year.
     *
     * Example: A value of 2010 would represent the association year 2010/2011
     *
     * @param DateTime $date
     *
     * @return int representing an association year
     */
    public function getAssociationYear($date)
    {
        if ($date->format('n') < self::ASSOCIATION_YEAR_START_MONTH) {
            return $date->format('Y') - 1;
        } else {
            return $date->format('Y');
        }
    }

    /**
     * Creates a new album.
     *
     * @param int $parentId the id of the parent album
     * @param array $data The post data to use for the album
     *
     * @return AlbumModel|bool
     *
     * @throws Exception
     */
    public function createAlbum($parentId, $data)
    {
        if (!$this->isAllowed('create')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to create albums'));
        }
        $form = $this->getCreateAlbumForm();
        $album = new AlbumModel();
        $form->bind($album);
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }
        if (!is_null($parentId)) {
            $album->setParent($this->getAlbum($parentId));
        }
        $this->albumMapper->persist($album);
        $this->albumMapper->flush();

        return $album;
    }

    /**
     * Retrieves the form for creating a new album.
     *
     * @return CreateAlbum
     */
    public function getCreateAlbumForm()
    {
        if (!$this->isAllowed('create')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to create albums.'));
        }

        return $this->createAlbumForm;
    }

    /**
     * Gets an album using the album id.
     *
     * @param int $albumId the id of the album
     * @param string $type "album"|"member"|"year"
     *
     * @return AlbumModel album matching the given id
     *
     * @throws Exception If there are not sufficient permissions
     */
    public function getAlbum($albumId, $type = 'album')
    {
        if (!$this->isAllowed('view')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to view albums'));
        }
        $album = null;
        switch ($type) {
            case 'album':
                $album = $this->albumMapper->getAlbumById($albumId);
                break;
            case 'member':
                $album = $this->getMemberAlbum($albumId);
                break;
            default:
                throw new Exception('Album type not allowed');
        }

        return $album;
    }

    public function getMemberAlbum($lidNr)
    {
        $member = $this->memberService->findMemberByLidnr($lidNr);
        if (null == $member) {
            return null;
        }
        $album = new MemberAlbum($lidNr, $member);
        $album->setName($member->getFullName());
        $album->setStartDateTime($member->getBirth()); // ugly fix
        $album->setEndDateTime(new DateTime());
        $album->addPhotos($this->photoService->getPhotos($album));

        return $album;
    }

    /**
     * Updates the metadata of an album using post data.
     *
     * @param int $albumId the id of the album to modify
     * @param array $data The post data to update
     *
     * @return bool indicating if the update was successful
     */
    public function updateAlbum($albumId, $data)
    {
        if (!$this->isAllowed('edit')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to edit albums'));
        }
        $form = $this->getEditAlbumForm($albumId);
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $this->albumMapper->flush();

        return true;
    }

    /**
     * Retrieves the form for editing the specified album.
     *
     * @param int $albumId of the album
     *
     * @return EditAlbum
     */
    public function getEditAlbumForm($albumId)
    {
        if (!$this->isAllowed('edit')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to edit albums.'));
        }
        $form = $this->editAlbumForm;
        $album = $this->getAlbum($albumId);
        $form->bind($album);

        return $form;
    }

    /**
     * Moves an album to new parent album.
     *
     * @param int $albumId the id of the album to be moved
     * @param int $parentId the id of the new parent
     *
     * @return bool indicating if the move was successful
     *
     * @throws Exception
     */
    public function moveAlbum($albumId, $parentId)
    {
        if (!$this->isAllowed('move')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to move albums'));
        }
        $album = $this->getAlbum($albumId);
        $parent = $this->getAlbum($parentId);
        if (is_null($album) || $albumId == $parentId) {
            return false;
        }

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
    public function deleteAlbum($albumId)
    {
        if (!$this->isAllowed('delete')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to delete albums.'));
        }
        $album = $this->getAlbum($albumId);
        if (!is_null($album)) {
            $this->albumMapper->remove($album);
            $this->albumMapper->flush();
        }
    }

    /**
     * Updates the given album with a newly generated cover photo.
     *
     * @param int $albumId
     *
     * @throws Exception
     */
    public function generateAlbumCover($albumId)
    {
        if (!$this->isAllowed('edit')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to generate album covers.'));
        }
        $album = $this->getAlbum($albumId);
        //if an existing cover photo was generated earlier, delete it.
        $coverPath = $this->albumCoverService->createCover($album);
        if (!is_null($album->getCoverPath())) {
            $this->storageService->removeFile($album->getCoverPath());
        }
        $album->setCoverPath($coverPath);
        $this->albumMapper->flush();
    }

    /**
     * Deletes the file belonging to the album cover for an album.
     *
     * @param AlbumModel $album
     */
    public function deleteAlbumCover($album)
    {
        $this->photoService->deletePhotoFile($album->getCoverPath());
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
    public function movePhoto($photoId, $albumId)
    {
        if (!$this->isAllowed('move')) {
            throw new NotAllowedException($this->translator->translate('Not allowed to move photos'));
        }

        $photo = $this->photoService->getPhoto($photoId);
        $album = $this->getAlbum($albumId);
        if (is_null($photo) || is_null($album)) {
            return false;
        }

        $photo->setAlbum($album);
        $this->albumMapper->flush();

        return true;
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
        return 'album';
    }
}
