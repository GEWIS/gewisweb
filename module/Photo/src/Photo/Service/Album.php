<?php

namespace Photo\Service;

use Application\Service\AbstractService;
use Photo\Model\Album as AlbumModel;

/**
 * Album service.
 */
class Album extends AbstractService
{

    /**
     * A GEWIS association year starts 01-07
     */
    const ASSOCIATION_YEAR_START_MONTH = 7;
    const ASSOCIATION_YEAR_START_DAY = 1;

    /**
     * Gets an album using the album id.
     *
     * @param integer $albumId the id of the album
     *
     * @return \Photo\Model\Album album matching the given id
     */
    public function getAlbum($albumId)
    {
        return $this->getAlbumMapper()->getAlbumById($albumId);
    }

    /**
     * Retrieves all the albums in the root directory or in the specified album.
     *
     * @param integer $start the result to start at
     * @param integer $maxResults max amount of results to return, null for infinite
     * @param \Photo\Model\Album $album The album to retrieve sub-albums of
     *
     * @return array of albums
     */
    public function getAlbums($album = null, $start = 0, $maxResults = null)
    {
        if ($album == null) {
            return $this->getAlbumMapper()->getRootAlbums();
        } else {
            return $this->getAlbumMapper()->getSubAlbums($album, $start, $maxResults);
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
        if (!is_int($year)) {
            return array();
        }

        $start = \DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $year . '-' . self::ASSOCIATION_YEAR_START_MONTH . '-' . self::ASSOCIATION_YEAR_START_DAY . ' 0:00:00'
        );
        $end = clone $start;
        $end->add(new \DateInterval('P1Y'));

        return $this->getAlbumMapper()->getAlbumsInDateRange($start, $end);
    }

    /**
     * Retrieves all root albums which do not have a startDateTime specified.
     * This is in most cases analogous to returning all empty albums.
     *
     * @return array of \Photo\Model\Album
     */
    public function getAlbumsWithoutDate()
    {
        return $this->getAlbumMapper()->getAlbumsWithoutDate();
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
        $oldest = $this->getAlbumMapper()->getOldestAlbum();
        $newest = $this->getAlbumMapper()->getNewestAlbum();
        if (is_null($oldest) || is_null($newest) || is_null($oldest->getStartDateTime()) || is_null($newest->getEndDateTime())) {
            return array(null);
        }

        $startYear = $this->getAssociationYear($oldest->getStartDateTime());
        $endYear = $this->getAssociationYear($newest->getEndDateTime());

        // We make the reasonable assumption that at least one photo is taken every year
        return range($startYear, $endYear);
    }

    /**
     * Creates a new album.
     *
     * @param int $parentId the id of the parent album
     * @param array $data The post data to use for the album
     *
     * @return \Photo\Model\Album|boolean
     */
    public function createAlbum($parentId, $data)
    {

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
        $this->getAlbumMapper()->persist($album);
        $this->getAlbumMapper()->flush();

        return $album;
    }

    /**
     * Updates the metadata of an album using post data
     *
     * @param int $albumId the id of the album to modify
     * @param array $data The post data to update
     *
     * @return boolean indicating if the update was successful
     */
    public function updateAlbum($albumId, $data)
    {
        $form = $this->getEditAlbumForm($albumId);
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $this->getAlbumMapper()->flush();

        return true;
    }

    /**
     * Moves an album to new parent album
     *
     * @param int $albumId the id of the album to be moved
     * @param int $parentId the id of the new parent
     *
     * @return boolean indicating if the move was successful
     */
    public function moveAlbum($albumId, $parentId)
    {
        $album = $this->getAlbum($albumId);
        $parent = $this->getAlbum($parentId);
        if (is_null($album) || $albumId == $parentId) {
            return false;
        }

        $album->setParent($parent);
        $this->getAlbumMapper()->flush();

        return true;
    }

    /**
     * Removes an album and all subalbums recursively, including all photos.
     *
     * @param int $albumId the id of the album to remove.
     */
    public function deleteAlbum($albumId)
    {
        $album = $this->getAlbum($albumId);
        if (!is_null($album)) {
            $this->getAlbumMapper()->remove($album);
            $this->getAlbumMapper()->flush();
        }
    }

    /**
     * Updates the given album with a newly generated cover photo.
     *
     * @param int $albumId
     */
    public function generateAlbumCover($albumId)
    {
        $config = $this->getConfig();
        $album = $this->getAlbum($albumId);
        //if an existing cover photo was generated earlier, delete it.
        $coverPath = $this->getAlbumCoverService()->createCover($album);
        if (!is_null($album->getCoverPath())) {
            unlink($config['upload_dir'] . '/' . $album->getCoverPath());
        }
        $album->setCoverPath($coverPath);
        $this->getAlbumMapper()->flush();
    }

    /**
     * Deletes the file belonging to the album cover for an album.
     *
     * @param \Photo\Model\Album $album
     */
    public function deleteAlbumCover($album)
    {
        $this->getPhotoService()->deletePhotoFile($album->getCoverPath());
    }

    /**
     * Returns the association year to which a certain date belongs
     * In this context an association year is defined as the year which contains
     * the first day of the association year.
     *
     * Example: A value of 2010 would represent the association year 2010/2011
     *
     * @param \DateTime $date
     *
     * @return int representing an association year.
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
     * Retrieves the form for editing the specified album.
     *
     * @param integer $albumId of the album
     * @return \Photo\Form\EditAlbum
     */
    public function getEditAlbumForm($albumId)
    {
        //TODO: permissions!!
        $form = $this->sm->get('photo_form_album_edit');
        $album = $this->getAlbum($albumId);
        $form->bind($album);

        return $form;
    }

    /**
     * Retrieves the form for creating a new album.
     *
     * @return \Photo\Form\CreateAlbum
     */
    public function getCreateAlbumForm()
    {
        //TODO: permissions!!
        return $this->sm->get('photo_form_album_create');
    }

    /**
     * Get the PhotoImport form.
     *
     * @return \Photo\Form\PhotoImport
     */
    public function getPhotoImportForm()
    {
        return $this->sm->get('photo_form_import_folder');
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
     * Get the photo mapper.
     *
     * @return \Photo\Mapper\Photo
     */
    public function getPhotoMapper()
    {
        return $this->sm->get('photo_mapper_photo');
    }
    
    /**
     * Gets the photo service.
     *
     * @return \Photo\Service\Photo
     */
    public function getPhotoService()
    {
        return $this->sm->get("photo_service_photo");
    }

    /**
     * Gets the album cover service.
     *
     * @return \Photo\Service\AlbumCover
     */
    public function getAlbumCoverService()
    {
        return $this->sm->get("photo_service_album_cover");
    }

    /**
     * Get the photo config
     *
     * @return array containing the config for the module
     */
    public function getConfig()
    {
        $config = $this->sm->get('config');

        return $config['photo'];
    }

}
