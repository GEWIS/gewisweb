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
     * Gets an album using the album id.
     *
     * @param integer $id the id of the album
     *
     * @return \Photo\Model\Album album matching the given id
     */
    public function getAlbum($id)
    {
        return $this->getAlbumMapper()->getAlbumById($id);
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
    public function getAlbumsByYear($year) {
        // A GEWIS year starts July 1st
        $start = \DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $year . '-' . self::ASSOCIATION_YEAR_START_MONTH . '-' . self::ASSOCIATION_YEAR_START_DAY . ' 0:00:00'
        );
        $end = clone $start;
        $end->add(new \DateInterval('P1Y'));
        return $this->getAlbumMapper()->getAlbumsInDateRange($start, $end);
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
    public function getAlbumYears() {
        $oldest = $this->getAlbumMapper()->getOldestAlbum();
        $latest = $this->getAlbumMapper()->getLatestAlbum();
        
        $startYear = $this->getAssociationYear($oldest);
        $endYear = $this->getAssociationYear($latest);

        // We make the reasonable assumption that at least one photo is taken every year
        return range($startYear, $endYear);
    }

    /**
     * Creates a new album.
     *
     * @param int $parentId the id of the parent album
     * @param array $data The post data to use for the album
     *
     * @return boolean indicating if the creation was successful
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

        return true;
    }

    /**
     * Updates the metadata of an album using post data
     *
     * @param int $id the id of the album to modify
     * @param array $data The post data to update
     *
     * @return boolean indicating if the update was successful
     */
    public function updateAlbum($id, $data)
    {
        $form = $this->getEditAlbumForm($id);
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
     * @param int $id the id of the album to be moved
     * @param int $parentId the id of the new parent
     *
     * @return boolean indicating if the move was successful
     */
    public function moveAlbum($id, $parentId)
    {
        $album = $this->getAlbum($id);
        $parent = $this->getAlbum($parentId);
        if (is_null($album) || $id == $parentId) {
            return false;
        }

        $album->setParent($parent);
        $this->getAlbumMapper()->flush();

        return true;
    }

    /**
     * Removes an album and all subalbums recursively, including all photos.
     *
     * @param int $id the id of the album to remove.
     */
    public function deleteAlbum($id)
    {
        $this->deleteAlbumPhotos($id);
        foreach ($this->getAlbumMapper()->getSubAlbums($id) as $subAlbum) {
            $this->deleteAlbum($subAlbum);
        }
        $this->getAlbumMapper()->deleteAlbum($id);
        $this->getAlbumMapper()->flush();
    }

    /**
     * Deletes all photos inside the album
     *
     * @param int $id the id of the album to delete all photos from
     */
    public function deleteAlbumPhotos($id)
    {
        $album = $this->getAlbum($id);
        foreach ($this->getAlbumMapper()->getAlbumPhotos($album) as $photo) {
            $this->getPhotoService()->deletePhoto($photo);
        }
    }

    /**
     * Updates the given album with a newly generated cover photo.
     *
     * @param int $id
     */
    public function generateAlbumCover($id)
    {
        $config = $this->getConfig();
        $album = $this->getAlbum($id);
        //if an existing cover photo was generated earlier, delete it.
        $coverPath = $this->getAlbumCoverService()->createCover($album);
        if (!is_null($album->getCoverPath())) {
            unlink($config['upload_dir'] . '/' . $album->getCoverPath());
        }
        $album->setCoverPath($coverPath);
        $mapper = $this->getAlbumMapper();
        $mapper->persist($album);
        $mapper->flush();
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
    public function getAssociationYear($date) {
        if($date->format('n') < ASSOCIATION_YEAR_START_MONTH) {
            return $date->format('Y') - 1;
        } else {
            return $date->format('Y');
        }
    }

    public function getEditAlbumForm($id)
    {
        //TODO: permissions!!
        $form = $this->sm->get('photo_form_album_edit');
        $album = $this->getAlbum($id);
        $form->bind($album);

        return $form;
    }

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
     * Gets the photo service.
     *
     * @return \Photo\Service\Photo
     */
    public function getPhotoService()
    {
        return $this->getServiceManager()->get("photo_service_photo");
    }

    /**
     * Gets the album cover service.
     *
     * @return \Photo\Service\AlbumCover
     */
    public function getAlbumCoverService()
    {
        return $this->getServiceManager()->get("photo_service_album_cover");
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
