<?php

namespace Photo\Service;

use Application\Service\AbstractService;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Photo\Model\Album as AlbumModel;
use Photo\Model\Photo as PhotoModel;

/**
 * Album service.
 */
class Album extends AbstractService
{

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
     * Gets an album using the album id
     * 
     * @param integer $id the id of the album
     * @return Photo\Model\Album album matching the given id
     */
    public function getAlbum($id)
    {
        return $this->getAlbumMapper()->getAlbumById($id);
    }

    /**
     * Get all the albums in the root directory
     * @param integer $start the result to start at
     * @param integer $maxResults max amount of results to return, null for infinite
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
     * Creates a new album.
     * 
     * @param String $name The name of the new album.
     * @param Photo\Model\Album $parent The parent of this album, if any.
     * @return The new album.
     */
    public function createAlbum($name, $parent = null)
    {

        $album = new AlbumModel();
        $album->setName($name);
        if (!is_null($parent)) {
            $album->setParent($parent);
        }

        $mapper = $this->getAlbumMapper();
        $mapper->persist($album);
        $mapper->flush();
        return $album;
    }

    /**
     * Updates the name of an existing album
     * 
     * @param int $id the id of the album to modify
     * @param String $name the new name for the album
     */
    public function updateAlbumName($id, $name)
    {
        $album = $this->getAlbum($id);
        $album->setName($name);
    }

    /**
     * Moves an album to new parent album
     * 
     * @param int $id the id of the album to be moved
     * @param int $newParent the id of the new parent
     */
    public function moveAlbum($id, $newParent)
    {
        $album = $this->getAlbum($id);
        $album->setParent($newParent);
    }

    /**
     * removes an album and all subalbums recusively, including all photos.
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
     * Updates the given album with a newly generated cover photo
     * @param type $alumId
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
        $mapper = $this->getAlbumMapper();
        $mapper->persist($album);
        $mapper->flush();
    }

    public function getCreateAlbumForm()
    {
        //TODO: permissions
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
     * @return Photo\Service\Photo
     */
    public function getPhotoService()
    {
        return $this->getServiceManager()->get("photo_service_photo");
    }

    /**
     * Gets the album cover service.
     * 
     * @return Photo\Service\AlbumCover
     */
    public function getAlbumCoverService()
    {
        return $this->getServiceManager()->get("photo_service_album_cover");
    }

    /**
     * Get the photo config
     *
     * @return array
     */
    public function getConfig()
    {
        $config = $this->sm->get('config');
        return $config['photo'];
    }

}
