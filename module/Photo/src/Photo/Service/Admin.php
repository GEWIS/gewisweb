<?php
/**
 * Created by PhpStorm.
 * User: justin
 * Date: 30-7-15
 * Time: 22:47
 */

namespace Photo\Service;

/**
 * Admin service for all photo admin related functions.
 */
class Admin {


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
     */
    public function getMemberService()
    {
        return $this->sm->get('decision_service_member');
    }

    /**
     * Gets the album service.
     *
     * @return \Photo\Service\Album
     */
    public function getPhotoService()
    {
        return $this->sm->get('photo_service_photo');
    }
}